<?php
/**
 * Alfa Capital Holdings (Cyprus) Limited.
 *
 * The following source code is PROPRIETARY AND CONFIDENTIAL. Use of this source code
 * is governed by the Alfa Capital Holdings (Cyprus) Ltd. Non-Disclosure Agreement
 * previously entered between you and Alfa Capital Holdings (Cyprus) Limited.
 *
 * By accessing, using, copying, modifying or distributing this software, you acknowledge
 * that you have been informed of your obligations under the Agreement and agree
 * to abide by those obligations.
 *
 * @author "Sergey Knigin" <sergey.knigin@alfaforex.com>
 */

namespace Mero\Monolog\Formatter;

use Monolog\Formatter\FormatterInterface;
use yii\base\Component;

final class WrapperComponent extends Component implements FormatterInterface
{
    /** @var string */
    public $formatterClass;

    /** @var array */
    public $params = [];

    /** @var FormatterInterface */
    private $formatter;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->formatter = $this->createFormatter();
    }

    public function format(array $record)
    {
        return $this->formatter->format($record);
    }

    public function formatBatch(array $records)
    {
        return $this->formatter->formatBatch($records);
    }

    /**
     * @return FormatterInterface
     */
    private function createFormatter()
    {
        $class = $this->formatterClass;
        
        return new $class(...$this->params);
    }
}