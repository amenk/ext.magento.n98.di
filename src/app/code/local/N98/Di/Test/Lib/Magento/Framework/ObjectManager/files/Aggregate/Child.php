<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Magento_Test_Di_Aggregate_Child extends \Magento_Test_Di_Aggregate_AggregateParent
{
    public $secondScalar;

    public $secondOptionalScalar;

    public function __construct(
        \Magento_Test_Di_DiInterface $interface,
        \Magento_Test_Di_DiParent $parent,
        \Magento_Test_Di_Child $child,
        $scalar,
        $secondScalar,
        $optionalScalar = 1,
        $secondOptionalScalar = ''
    ) {
        parent::__construct($interface, $parent, $child, $scalar, $optionalScalar);
        $this->secondScalar = $secondScalar;
        $this->secondOptionalScalar = $secondOptionalScalar;
    }
}