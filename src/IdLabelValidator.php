<?php

namespace ruskush\validators;

use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * Этот валидатор проверяет, что значение указанного атрибута присутствует в массиве, описанном в свойстве targetArray.
 * Если значение атрибута является ключём элемента массива targetArray - то значение атрибута остаётся без изменения.
 * Если значение атрибута является значением элемента массива targetArray - то в значение атрибута помещается ключ
 * соответствующего элемента массива targetArray. *
 *  - targetArray: массив, в котором ищутся допустимые значения атрибута
 *  - allowArray: допустимо ли в атрибут передавать массив значений. По умолчанию - false. Если свойство установить
 *    в true и тип входящих данных - массив, тогда каждый его элемент должен существовать в массиве targetArray.
 *
 * Валидатор удобно применять к тем аттрибутам, в которые нужно присваивать значения из определённого набора констант,
 * для которых соответствуют "человекопонятные" описания, приходящие от пользователя, парсера и т.д.
 *
 * Примеры использования валидатора:
 * ```php
 * // Значение type должно быть равным одному из значений: 'id1', 'label1', 'id2', 'label2'.
 * // Если значение атрибута type будет равным 'id1' или 'id2', то оно останется неизменным.
 * // Если значение атрибута type будет равным 'label1' или 'label2' - то в атрибут type присвоится 'id1' или 'id2'
 * [['type'], IdLabelValidator::class, 'targetArray' => ['id1' => 'label1', 'id2' => 'label2']],
 *
 * // Если в атрибут kind передать массив ['id1', 'label2'],то валидатор поменяет его на ['id1', 'id2'].
 * // Если хоть одно из значений массива в атрибуте kind не удасться найти в массиве targetArray - валидация
 * // не будет пройдена.
 * [['kind'], IdLabelValidator::class, 'targetArray' => ['id1' => 'label1', 'id2' => 'label2'], 'allowArray' => true],
 * ```
 */
class IdLabelValidator extends Validator {
    /**
     * @var array массив, в котором ищутся допустимые значения атрибута
     */
    public $targetArray = [];

    /**
     * @var bool допустимо ли в атрибут передавать массив значений
     */
    public $allowArray = false;

    /**
     * @throws InvalidConfigException
     */
    public function init() {
        parent::init();
        if (empty($this->targetArray) || !is_array($this->targetArray)) {
            throw new InvalidConfigException('Свойство targetArray должно быть массивом');
        }
        if ($this->message === null) {
            $this->message = 'Атрибут {attribute} имеет неверное значение';
        }
    }

    /**
     * @inheritDoc
     */
    public function validateAttribute($model, $attribute) {
        $idLabel = $this->targetArray;
        $inputIsArray = is_array($model->$attribute);

        if ($inputIsArray) {
            if (!$this->allowArray) {
                $this->addError($model, $attribute, 'Атрибут {attribute} не может быть массивом');
                return;
            }
            $values = $model->$attribute;
        } else {
            $values = [$model->$attribute];
        }

        $result = [];
        foreach ($values as $value) {
            if (isset($idLabel[$value])) {
                $result[] = $value;
                continue;
            }
            if (($id = array_search($value, $idLabel)) !== false) {
                $result[] = $id;
                continue;
            }
            $this->addError($model, $attribute, $this->message);
        }

        if (!$model->hasErrors($attribute)) {
            $model->$attribute = $inputIsArray ? $result : $result[0];
        }
    }
}
