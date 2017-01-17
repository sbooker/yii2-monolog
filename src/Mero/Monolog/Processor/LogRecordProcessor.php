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

namespace Mero\Monolog\Processor;

interface LogRecordProcessor
{
    /**
     * @param array $record
     *
     * @return array
     */
    public function process(array $record);
}