<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector\Mocks\Topics;

/**
 * Copy of https://github.com/oroinc/platform/tree/4.2.0/src/Oro/Bundle/EmailBundle/Async/Topics.php#L8
 * Copied for the test purposes.
 *
 * List of the EmailBundle`s topics.
 */
class Topics
{
    const SEND_AUTO_RESPONSE = 'oro.email.send_auto_response';
    const SEND_AUTO_RESPONSES = 'oro.email.send_auto_responses';
    const SEND_EMAIL_TEMPLATE = 'oro.email.send_email_template';
    const ADD_ASSOCIATION_TO_EMAIL = 'oro.email.add_association_to_email';
    const ADD_ASSOCIATION_TO_EMAILS = 'oro.email.add_association_to_emails';
    const UPDATE_ASSOCIATIONS_TO_EMAILS = 'oro.email.update_associations_to_emails';
    const UPDATE_EMAIL_OWNER_ASSOCIATION = 'oro.email.update_email_owner_association';
    const UPDATE_EMAIL_OWNER_ASSOCIATIONS = 'oro.email.update_email_owner_associations';
    const SYNC_EMAIL_SEEN_FLAG = 'oro.email.sync_email_seen_flag';
    const PURGE_EMAIL_ATTACHMENTS = 'oro.email.purge_email_attachments';
    const PURGE_EMAIL_ATTACHMENTS_BY_IDS = 'oro.email.purge_email_attachments_by_ids';
}
