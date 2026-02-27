<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Integration;

use Oro\UpgradeToolkit\Semadiff\Classifier\ChangeClassifier;
use Oro\UpgradeToolkit\Semadiff\Comparator\FileComparator;
use Oro\UpgradeToolkit\Semadiff\Comparator\FileComparisonResult;
use PHPUnit\Framework\TestCase;

/**
 * Tests the categorizer against real before/after file pairs from the ORO upgrade-6.1 branch.
 * Each fixture is a condensed but faithful replica of the actual file changes.
 *
 * Change patterns covered:
 * - COSMETIC: #[\Override] addition, docblock removal, ternary spacing, class docblock addition,
 *             combined docblock+spacing+Override, constructor formatting
 * - SIGNATURE: return type additions (:void, :string, :?ValueGuess, :?TypeGuess),
 *              &-reference removal from params, #[\Override]+return type combined
 * - LOGIC: method call rename (getId→getInternalId), direct→static helper call,
 *          string→::class constant, annotation→attribute+extends change+trait swap,
 *          interface swap+property rename, constructor promotion+null-safe operator,
 *          method/property removal+trait addition
 */
final class KnownFilesTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/fixtures';

    private FileComparator $comparator;
    private ChangeClassifier $classifier;

    protected function setUp(): void
    {
        $this->comparator = new FileComparator();
        $this->classifier = new ChangeClassifier();
    }

    private function classifyFile(string $filename): array
    {
        $beforeFile = self::FIXTURES_DIR . '/before/' . $filename;
        $afterFile = self::FIXTURES_DIR . '/after/' . $filename;

        $this->assertFileExists($beforeFile, "Before fixture missing: $filename");
        $this->assertFileExists($afterFile, "After fixture missing: $filename");

        $result = $this->comparator->compare(
            file_get_contents($beforeFile),
            file_get_contents($afterFile),
        );

        return [
            'category' => $this->classifier->classify($result),
            'result' => $result,
        ];
    }

    // ========================================================================
    // COSMETIC files — only comments, #[\Override], whitespace, docblocks
    // ========================================================================

    /**
     * CustomerSelectTypeExtension.php: added #[\Override] to two methods.
     * Pattern: pure Override attribute addition.
     */
    public function testCosmeticCustomerSelectTypeExtension(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('CustomerSelectTypeExtension.php');
        $this->assertSame(ChangeClassifier::COSMETIC, $cat, $this->detailsToString($r));
    }

    /**
     * MoveFilters.php: added #[\Override] to two methods.
     * Pattern: pure Override attribute addition on interface implementation.
     */
    public function testCosmeticMoveFilters(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('MoveFilters.php');
        $this->assertSame(ChangeClassifier::COSMETIC, $cat, $this->detailsToString($r));
    }

    /**
     * OroLabCartBundle.php: added class-level docblock only.
     * Pattern: class docblock addition, no code changes.
     */
    public function testCosmeticOroLabCartBundle(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('OroLabCartBundle.php');
        $this->assertSame(ChangeClassifier::COSMETIC, $cat, $this->detailsToString($r));
    }

    /**
     * AbstractActivityProcessor.php: changed `? :` to `?:` (ternary spacing).
     * Pattern: whitespace change inside elvis operator — no AST difference.
     */
    public function testCosmeticAbstractActivityProcessor(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('AbstractActivityProcessor.php');
        $this->assertSame(ChangeClassifier::COSMETIC, $cat, $this->detailsToString($r));
    }

    /**
     * AbstractAccountPlanMetricProvider.php: removed `{@inheritdoc}` docblock.
     * Pattern: docblock removal, no code changes.
     */
    public function testCosmeticAbstractAccountPlanMetricProvider(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('AbstractAccountPlanMetricProvider.php');
        $this->assertSame(ChangeClassifier::COSMETIC, $cat, $this->detailsToString($r));
    }

    /**
     * AbstractCategoryCodeType.php: removed docblocks, added #[\Override],
     * added class docblock, reformatted constructor params.
     * Pattern: combined cosmetic changes (docblock+Override+whitespace+formatting).
     */
    public function testCosmeticAbstractCategoryCodeType(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('AbstractCategoryCodeType.php');
        $this->assertSame(ChangeClassifier::COSMETIC, $cat, $this->detailsToString($r));
    }

    // ========================================================================
    // SIGNATURE files — return type additions, param type changes
    // ========================================================================

    /**
     * CustomerTierType.php: added `:void` return types to buildForm() and
     * configureOptions(), added #[\Override], added class docblock.
     * Pattern: return type additions (no-return → :void).
     */
    public function testSignatureCustomerTierType(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('CustomerTierType.php');
        $this->assertSame(ChangeClassifier::SIGNATURE, $cat, $this->detailsToString($r));
    }

    /**
     * Combinator.php: removed `&` (pass-by-reference) from array params in 3 methods.
     * Pattern: byRef param removal = real signature change (not cosmetic).
     */
    public function testSignatureCombinator(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('Combinator.php');
        $this->assertSame(ChangeClassifier::SIGNATURE, $cat, $this->detailsToString($r));
    }

    /**
     * CustomerTypeCustomerSelectType.php: added `:void` to configureOptions(),
     * added #[\Override] to both methods.
     * Pattern: return type addition + Override attribute.
     */
    public function testSignatureCustomerTypeCustomerSelectType(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('CustomerTypeCustomerSelectType.php');
        $this->assertSame(ChangeClassifier::SIGNATURE, $cat, $this->detailsToString($r));
    }

    /**
     * AbstractEntityFieldsFormGuesser.php: added `:?ValueGuess` and `:?TypeGuess`
     * return types, added #[\Override], added new `use` import.
     * Pattern: nullable return type additions to existing untyped methods.
     */
    public function testSignatureAbstractEntityFieldsFormGuesser(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('AbstractEntityFieldsFormGuesser.php');
        $this->assertSame(ChangeClassifier::SIGNATURE, $cat, $this->detailsToString($r));
    }

    // ========================================================================
    // LOGIC files — body changes, structural changes, method renames
    // ========================================================================

    /**
     * GoOpportunityOwnerSelectType.php: added return types (signature) BUT ALSO
     * changed `->getMainRequest()->get()` to `->getMainRequest()?->get()` (null-safe operator).
     * Pattern: signature+body change — body change makes it LOGIC.
     */
    public function testLogicGoOpportunityOwnerSelectType(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('GoOpportunityOwnerSelectType.php');
        $this->assertSame(ChangeClassifier::LOGIC, $cat, $this->detailsToString($r));
        // Verify it detected the specific body change
        $this->assertTrue($r->bodyChanged, 'Should detect body change from null-safe operator');
    }

    /**
     * DTQuoteOpportunityGridParamsProvider.php: `getId()` → `getInternalId()`.
     * THE critical test — the previous AST approach missed this because it only
     * compared node types, not method names.
     * Pattern: method call rename in body.
     */
    public function testLogicDTQuoteOpportunityGridParamsProvider(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('DTQuoteOpportunityGridParamsProvider.php');
        $this->assertSame(ChangeClassifier::LOGIC, $cat, $this->detailsToString($r));
        $this->assertTrue($r->bodyChanged);
        $this->assertFalse($r->classStructureChanged);
    }

    /**
     * SmOrderTypeOrderModifier.php: `$type->getId()` → `EnumHelper::getSafeInternalId($type)`.
     * Pattern: instance method call replaced by static helper call.
     */
    public function testLogicSmOrderTypeOrderModifier(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('SmOrderTypeOrderModifier.php');
        $this->assertSame(ChangeClassifier::LOGIC, $cat, $this->detailsToString($r));
        $this->assertTrue($r->bodyChanged);
    }

    /**
     * FormViewListenerDecorator.php: `'OroCustomerBundle:CustomerUser'` → `CustomerUser::class`.
     * Pattern: string literal replaced by ::class constant reference.
     */
    public function testLogicFormViewListenerDecorator(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('FormViewListenerDecorator.php');
        $this->assertSame(ChangeClassifier::LOGIC, $cat, $this->detailsToString($r));
        $this->assertTrue($r->bodyChanged);
    }

    /**
     * OrderErrorCase.php: annotation→attribute migration WITH class structure change:
     * `extends ExtendOrderErrorCase` → `implements ExtendEntityInterface` + `use ExtendEntityTrait`,
     * constructor body changed (parent::__construct() removed).
     * Pattern: extends/implements/trait swap + constructor change.
     */
    public function testLogicOrderErrorCase(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('OrderErrorCase.php');
        $this->assertSame(ChangeClassifier::LOGIC, $cat, $this->detailsToString($r));
        $this->assertTrue($r->classStructureChanged, 'Should detect extends/implements change');
    }

    /**
     * ProductSelector.php: annotation→attribute migration WITH:
     * - `extends ExtendProductSelector` removed → now uses `ExtendEntityTrait`
     * - `implements ExtendEntityInterface` added
     * - property types added (untyped → `?\DateTimeInterface $createdAt = null`)
     * Pattern: full annotation→attribute migration with structural changes.
     */
    public function testLogicProductSelector(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('ProductSelector.php');
        $this->assertSame(ChangeClassifier::LOGIC, $cat, $this->detailsToString($r));
        $this->assertTrue($r->classStructureChanged, 'Should detect extends removal');
    }

    /**
     * AddLeadFields.php: `ExtendExtensionAwareInterface` → `OutdatedExtendExtensionAwareInterface`,
     * removed `$extendExtension` property + `setExtendExtension()` method,
     * added `OutdatedExtendExtensionAwareTrait`,
     * body: `$this->extendExtension->` → `$this->outdatedExtendExtension->`.
     * Pattern: interface swap + trait addition + method/property removal + body property rename.
     */
    public function testLogicAddLeadFields(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('AddLeadFields.php');
        $this->assertSame(ChangeClassifier::LOGIC, $cat, $this->detailsToString($r));
        $this->assertTrue($r->classStructureChanged || $r->membersAddedOrRemoved || $r->bodyChanged);
    }

    /**
     * OrderErrorCaseMarkItemFoundChecker.php: constructor property promotion,
     * `$orderErrorCase->getId()` → `$orderErrorCase?->getId()` (null-safe),
     * `->getStatus()->getId()` → `->getStatus()->getInternalId()`.
     * Pattern: constructor promotion + null-safe operator + method call rename.
     */
    public function testLogicOrderErrorCaseMarkItemFoundChecker(): void
    {
        ['category' => $cat, 'result' => $r] = $this->classifyFile('OrderErrorCaseMarkItemFoundChecker.php');
        $this->assertSame(ChangeClassifier::LOGIC, $cat, $this->detailsToString($r));
        $this->assertTrue($r->bodyChanged, 'Should detect getId→getInternalId body change');
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    private function detailsToString(FileComparisonResult $r): string
    {
        $flags = [];
        if ($r->classStructureChanged) {
            $flags[] = 'classStructure';
        }
        if ($r->signatureChanged) {
            $flags[] = 'signature';
        }
        if ($r->bodyChanged) {
            $flags[] = 'body';
        }
        if ($r->membersAddedOrRemoved) {
            $flags[] = 'members';
        }

        $details = implode('; ', $r->details);

        return sprintf('[%s] %s', implode(', ', $flags), $details);
    }
}
