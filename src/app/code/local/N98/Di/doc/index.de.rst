*************
Modul: N98_Di
*************

Beispielkonfiguration
=====================

.. code-block:: xml

    <?xml version="1.0"?>
    <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:noNamespaceSchemaLocation="../../../../../../lib/Magento/Framework/ObjectManager/etc/config.xsd">
        <!-- Replace class with another -->
        <preference for="N98_Di_Model_Sample_Foo" type="N98_Di_Model_Sample_Bar" />

        <!-- Good old rewrite -->
        <preference for="Mage_Core_Helper_Data" type="Mage_Core_Helper_String" />

        <!-- Implement interface -->
        <preference for="N98_Di_Model_Sample_BarInterface" type="N98_Di_Model_Sample_Bar" />

        <virtualType name="myVirtualType" type="N98_Di_Model_Sample_Foo" />
        <virtualType name="myProduct" type="Mage_Catalog_Model_Product" />

        <type name="N98_Di_Model_Sample_Bar">
            <arguments>
                <argument name="value" xsi:type="string">Hallo Welt</argument>
            </arguments>
        </type>

        <type name="N98_Di_Model_Sample_Zoz">
            <arguments>
                <argument name="a" xsi:type="object">myVirtualType</argument>
                <argument name="product" xsi:type="object">myProduct</argument>
            </arguments>
        </type>
    </config>
