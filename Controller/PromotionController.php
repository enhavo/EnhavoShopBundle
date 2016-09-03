<?php
/**
 * PromotionController.php
 *
 * @since 19/08/16
 * @author gseidel
 */

namespace Enhavo\Bundle\ShopBundle\Controller;

use Enhavo\Bundle\ShopBundle\Model\OrderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sylius\Component\Cart\Provider\CartProviderInterface;
use Sylius\Component\Cart\Model\CartInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sylius\Component\Cart\Event\CartEvent;
use Symfony\Component\EventDispatcher\GenericEvent;
use Sylius\Component\Resource\Event\FlashEvent;
use Sylius\Component\Cart\SyliusCartEvents;

class PromotionController extends Controller
{
    public function redeemCouponAction(Request $request)
    {
        $order = $this->getCurrentCart();
        $form = $this->createForm('enhavo_shop_order_promotion_coupon', $order);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $event = new CartEvent($order);
            $eventDispatcher = $this->getEventDispatcher();
            $eventDispatcher->dispatch(SyliusCartEvents::CART_CHANGE, new GenericEvent($order));
            $eventDispatcher->dispatch(SyliusCartEvents::CART_SAVE_INITIALIZE, $event);
            $eventDispatcher->dispatch(SyliusCartEvents::CART_SAVE_COMPLETED, new FlashEvent());
            $this->getManager()->flush();
        }

        if($request->isXmlHttpRequest()) {
            /** @var OrderInterface $order */
            $calculator = $this->get('enhavo_shop.calculator.order_composition_calculator');
            $orderComposition = $calculator->calculateOrder($order);
            return new JsonResponse([
                'order' => $orderComposition->toArray()
            ]);
        }

        $redirectUrl = $request->get('redirectUrl');
        if($redirectUrl) {
            return $this->redirect($redirectUrl);
        }

        return $this->render('EnhavoShopBundle:Promotion:redeem-coupon.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function cancelCouponAction(Request $request)
    {
        /** @var OrderInterface $order */
        $order = $this->getCurrentCart();
        $order->setPromotionCoupon(null);

        $event = new CartEvent($order);
        $eventDispatcher = $this->getEventDispatcher();
        $eventDispatcher->dispatch(SyliusCartEvents::CART_CHANGE, new GenericEvent($order));
        $eventDispatcher->dispatch(SyliusCartEvents::CART_SAVE_INITIALIZE, $event);
        $eventDispatcher->dispatch(SyliusCartEvents::CART_SAVE_COMPLETED, new FlashEvent());
        $this->getManager()->flush();

        if($request->isXmlHttpRequest()) {
            return new JsonResponse();
        }

        $redirectUrl = $request->get('redirectUrl');
        if($redirectUrl) {
            return $this->redirect($redirectUrl);
        }

        return $this->render('EnhavoShopBundle:Promotion:cancel-coupon.html.twig');
    }

    /**
     * @return CartProviderInterface
     */
    protected function getCartProvider()
    {
        return $this->get('sylius.cart_provider');
    }

    /**
     * @return CartInterface
     */
    protected function getCurrentCart()
    {
        return $this->getCartProvider()->getCart();
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getManager()
    {
        return $this->get('doctrine.orm.entity_manager');
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->get('event_dispatcher');
    }
}