# Тесты для AddressValidationActionParamConverterAttributeToMapEntityAttributeRector

Этот директория содержит тесты для правила Rector, которое преобразует атрибуты `ParamConverter` и `Entity` в атрибуты `MapEntity` для методов `addressValidationAction` в контроллерах наследниках `AbstractAddressValidationController`.

## Структура тестов

### Основной тестовый класс
- `AddressValidationActionParamConverterAttributeToMapEntityAttributeRectorTest.php` - главный тестовый класс

### Конфигурация
- `config/configured_rule.php` - конфигурация для запуска правила в тестах

### Тестовые фикстуры

1. **basic_param_converter_transformation.php.inc**
   - Базовая трансформация ParamConverter в MapEntity
   - Тестирует основную функциональность правила

2. **abstract_address_validation_controller.php.inc**
   - Тестирует работу с наследниками `AbstractAddressValidationController`
   - Проверяет корректную трансформацию в базовом контроллере

3. **with_existing_parameters.php.inc**
   - Тестирует добавление нового параметра к существующим
   - Проверяет сохранение порядка параметров

4. **should_not_change_non_target_controllers.php.inc**
   - Негативный тест: правило не должно изменять контроллеры, не наследующие целевые классы
   - Проверяет селективность правила

5. **multiple_param_converters.php.inc**
   - Тестирует обработку нескольких ParamConverter атрибутов в одном методе
   - Проверяет корректную трансформацию множественных параметров

6. **no_param_converter_in_target_method.php.inc**
   - Тестирует случай, когда в целевом методе нет ParamConverter
   - Проверяет, что правило не изменяет методы без атрибутов

7. **private_method_should_not_change.php.inc**
   - Негативный тест: правило не должно изменять приватные методы
   - Проверяет ограничения видимости методов

8. **entity_attribute_transformation.php.inc**
   - Тестирует трансформацию атрибута `Entity` в дополнение к `ParamConverter`
   - Проверяет работу с expr параметрами

9. **existing_parameter_with_same_name.php.inc**
   - Тестирует случай, когда параметр с таким именем уже существует
   - Проверяет предотвращение дублирования параметров

10. **complex_mapping_with_request_param.php.inc**
    - Тестирует сложные случаи с существующими параметрами Request
    - Проверяет корректное добавление кода установки атрибутов

11. **expr_param_converter_with_other_methods.php.inc**
    - Тестирует работу с expr параметрами и другими методами в том же классе
    - Проверяет селективность правила по методам

## Запуск тестов

Для запуска тестов используйте команду:

```bash
vendor/bin/phpunit tests/Rector/Rules/Oro70/FrameworkExtraBundle/ParamConverter/AddressValidationActionParamConverterAttributeToMapEntityAttributeRector/
```

## Покрытие функциональности

Тесты покрывают следующие аспекты:

- ✅ Основную трансформацию ParamConverter → MapEntity
- ✅ Работу с двумя целевыми базовыми классами контроллеров
- ✅ Обработку множественных атрибутов
- ✅ Добавление параметров и кода установки атрибутов
- ✅ Селективность по именам методов (addressValidationAction)
- ✅ Селективность по типам контроллеров
- ✅ Обработку атрибутов Entity в дополнение к ParamConverter
- ✅ Предотвращение дублирования параметров
- ✅ Ограничения видимости методов (только публичные)
- ✅ Сложные сценарии с существующими параметрами
