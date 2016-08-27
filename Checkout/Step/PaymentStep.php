<?php

namespace Enhavo\Bundle\ShopBundle\Checkout\Step;

use Enhavo\Bundle\ShopBundle\Entity\Order;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Symfony\Component\Form\FormInterface;

class PaymentStep extends CheckoutStep
{
    /**
     * {@inheritdoc}
     */
    public function displayAction(ProcessContextInterface $context)
    {
        $order = $this->getCurrentCart();
        $form = $this->createForm('enhavo_shop_order_payment', $order);
        return $this->renderStep($order, $form);
    }

    /**
     * {@inheritdoc}
     */
    public function forwardAction(ProcessContextInterface $context)
    {
        $order = $this->getCurrentCart();
        $form = $this->createForm('enhavo_shop_order_payment', $order);

        $request = $context->getRequest();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            /** @var $order Order */
            $order->getPayment()->setCurrencyCode('de');

            $this->getManager()->flush();
            return $this->complete();
        }

        return $this->renderStep($order, $form);
    }

    protected function renderStep(OrderInterface $order, FormInterface $form)
    {
        return $this->render('EnhavoShopBundle:Checkout:payment.html.twig', [
            'order' => $order,
            'form' => $form->createView()
        ]);
    }
}