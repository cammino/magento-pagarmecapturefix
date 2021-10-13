<?php
class Cammino_Pagarmecapturefix_Model_Observer
{
    protected $pagarmecc;

    // Constructor
    function __construct()
    {
        $this->pagarmecc = Mage::getModel('pagarme/cc');
    }

    // Observer responsible for get order when it finished
    public function orderSuccess(Varien_Object $data)
    {    
        $order = $data->getOrder();
        $payment = $order->getPayment();

        if ($payment->getMethod() == "pagarme_cc") {
            $this->generateInvoice($order);
        }
    }

    // Create a new invoice to force change order status
    // automatic made by credit card
    private function generateInvoice($order)
    {
        try {
            
            if ( !$order->canInvoice() ) {
                return false;
            }
            
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            
            $invoice->register();
            $invoice->setEmailSent(true);
            $invoice->getOrder()->setCustomerNoteNotify(1);
            $invoice->getOrder()->setIsInProcess(true);
            
            $order->addStatusHistoryComment('Captura feita no recibo.', false);
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();
            
            try {
                $invoice->sendEmail(true, '');
            } 
            catch (Exception $e) {
            }
            
            $order->save();
        } 
        catch (Exception $e) {
            return false;
        }
    }
}