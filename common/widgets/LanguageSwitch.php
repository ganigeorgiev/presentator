<?php
namespace common\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Url;
use yii\helpers\Html;

/**
 * LanguageSwitch widget that renders a language select box.
 *
 * @example
 * // simple
 * <?= LanguageSwitch::widget(); ?>
 *
 * // advanced
 * <?= LanguageSwitch::widget([
 *     'options' => [
 *         'class' => 'myCustomClass'
 *     ]
 * ]) ?>
 *
 * @author Gani Georgiev <gani.georgiev@gmail.com>
 */
class LanguageSwitch extends Widget
{
    /**
     * Enables you to define custom attributes to the wrapper container tag.
     *
     * @example
     * $options = [
     *     'class' => 'no-radius-b-l no-radius-b-r',
     *     'data-cursor-tooltup' => 'Lorem ipsum dolor sit amet',
     * ];
     *
     * @var array
     */
    public $options = [];

    /**
     * @inheritdoc
     */
    public function run()
    {
        $currentLang = strtolower(Yii::$app->language);

        $customClasses = isset($this->options['class']) ? ' ' . $this->options['class'] : '';
        unset($this->options['class']);
        $tagAttributes = Html::renderTagAttributes($this->options);

        $result = '<div class="language-widget ' . $customClasses .'" ' . $tagAttributes . '>';
        $result .= '<label for="language_select">' . Yii::t('app', 'Language') . '</label>';
        $result .= '<select id="language_select" class="language-select">';

        // language options
        $result .= sprintf('<option value="%s" %s>BG | %s</option>',
            Url::current(['lang' => 'bg']),
            ($currentLang == 'bg-bg' ? 'selected' : ''),
            Yii::t('app', 'Bulgarian')
        );
        $result .= sprintf('<option value="%s" %s>DE | %s</option>',
            Url::current(['lang' => 'de']),
            ($currentLang == 'de-de' ? 'selected' : ''),
            Yii::t('app', 'German')
        );
        $result .= sprintf('<option value="%s" %s>EN | %s</option>',
            Url::current(['lang' => 'en']),
            ($currentLang == 'en-us' ? 'selected' : ''),
            Yii::t('app', 'English')
        );
        $result .= sprintf('<option value="%s" %s>ES | %s</option>',
            Url::current(['lang' => 'es']),
            ($currentLang == 'es-es' ? 'selected' : ''),
            Yii::t('app', 'Spanish')
        );
        $result .= sprintf('<option value="%s" %s>FR | %s</option>',
            Url::current(['lang' => 'fr']),
            ($currentLang == 'fr-fr' ? 'selected' : ''),
            Yii::t('app', 'French')
        );
        $result .= sprintf('<option value="%s" %s>PL | %s</option>',
            Url::current(['lang' => 'pl']),
            ($currentLang == 'pl-pl' ? 'selected' : ''),
            Yii::t('app', 'Polish')
        );
        $result .= sprintf('<option value="%s" %s>PT-BR | %s</option>',
            Url::current(['lang' => 'pt-br']),
            ($currentLang == 'pt-br' ? 'selected' : ''),
            (Yii::t('app', 'Portuguese'))
        );
        $result .= sprintf('<option value="%s" %s>SQ-AL | %s</option>',
            Url::current(['lang' => 'sq-al']),
            ($currentLang == 'sq-al' ? 'selected' : ''),
            Yii::t('app', 'Albanian')
        );

        $result .= '</select>';
        $result .= '</div>';

        return $result;
    }
}
