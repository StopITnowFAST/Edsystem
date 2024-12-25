<?php

// namespace App\Controller;

// use App\Entity\Flower;
// use App\Entity\Storage;
// use App\Entity\Cart;
// use App\Entity\Order;
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\HttpFoundation\RedirectResponse;
// use Symfony\Component\Routing\Attribute\Route;
// use Symfony\Component\HttpFoundation\Response;
// use Doctrine\ORM\EntityManagerInterface;
// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// class CartController extends AbstractController
// {
//     private $em;
//     function __construct(EntityManagerInterface $em)
//     {
//         $this->em = $em;
//     }

//     #[Route(path:"/cart", name:"view_cart")]
//     function cart(Request $request, EntityManagerInterface $em): Response
//     {
//         // Получение корзины пользователя
//         $cartItems = $em->getRepository(Cart::class)->findBy(['cart_id' => $request->getSession()->getId()]);

//         // Получение всех цветов
//         $flowerRepo = $em->getRepository(Flower::class);
//         $flowers = $flowerRepo->findAll();

//         // Индексация цветов по ID
//         $flowersById = [];
//         foreach ($flowers as $flower) {
//             $flowersById[$flower->getId()] = $flower;
//         }

//         // Получение доступного количества на складе
//         $storageRepo = $em->getRepository(Storage::class);
//         $storages = $storageRepo->findAll();

//         // Индексация записи склада по flower_id
//         $storagesByFlowerId = [];
//         foreach ($storages as $storage) {
//             $storagesByFlowerId[$storage->getFlowerId()] = $storage;
//         }

//         return $this->render('cart.html.twig', [
//             'cartItems' => $cartItems,
//             'flowers' => $flowersById,
//             'storages' => $storagesByFlowerId,
//             'cartId' => $request->getSession()->getId(),
//         ]);
//     }


//     #[Route(path:"/add_to_cart/{id}", name:"add_to_cart")]
//     public function addToCart($id, Request $request): RedirectResponse
//     {
//         $flower = $this->em->getRepository(Flower::class)->find($id);

//         if ($flower) {
//             $sessionId = $request->getSession()->getId();
//             $cartItem = $this->em->getRepository(Cart::class)->findOneBy([
//                 'cart_id' => $sessionId,
//                 'flower_id' => $flower->getId(),
//             ]);
//             if (!$cartItem) {
//                 $cartItem = new Cart();
//                 $cartItem->setCartId($sessionId);
//                 $cartItem->setFlowerId($flower->getId());
//             }
//             $this->em->persist($cartItem);
//             $this->em->flush();
//         }

//         return $this->redirectToRoute('catalog');
//     }

//     #[Route(path:"/checkout", name:"checkout")]
//     public function checkout(Request $request)
//     {
//         $cartId = strtoupper($request->request->get('cart_id'));
//         $items = $request->request->all()['items'];
//         $number = $request->request->get('phone_number');
    
//         if (!$items) {
//             return new Response('No items in cart', 400);
//         }
    
//         foreach ($items as $item) {
//             $flowerId = $item['flower_id'];
//             $amount = $item['amount'];
    
//             $order = new Order;
    
//             $order->setOrderId($cartId);
//             $order->setFlowerId($flowerId);
//             $order->setAmount($amount);
//             $order->setPhoneNumber($number);
//             $order->setStatus(1);
    
//             $this->em->persist($order);
//             $this->em->flush();
//         }
    
//         // Устанавливаем флеш-сообщение
//         $this->addFlash('success', 'Ваш заказ успешно оформлен!');
    
//         return $this->redirectToRoute('catalog');
//     }
    
// }
