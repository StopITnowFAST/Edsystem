<?php

// // namespace App\Controller;

// use App\Entity\Flower;
// use App\Entity\Cart;
// use App\Entity\Order;
// use App\Entity\Storage;
// use App\Form\FlowerFormType;
// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use App\Entity\File;
// use App\Form\StorageFormType;
// use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// use Symfony\Component\Routing\Attribute\Route;
// use Doctrine\ORM\EntityManagerInterface;
// use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\HttpFoundation\File\Exception\FileException;
// use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
// use Symfony\Component\HttpFoundation\RedirectResponse;
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\String\Slugger\SluggerInterface;
// use Psr\Log\LoggerInterface;

// class AdminController extends AbstractController {

//     private $em;
//     private $logger;
//     function __construct(
//         EntityManagerInterface $em,
//         LoggerInterface $logger,
//     ) {
//         $this->em = $em;
//         $this->logger = $logger;        
//     }

//     // Страница с цветами
//     #[Route(path: '/admin/flower', name: 'flower_list')] 
//     function flowers(Request $request, SluggerInterface $slugger) {
//         dump($request->getSession()->getId());
//         $flowers = $this->em->getRepository(Flower::class)->findAll();

//         $flower = new Flower();
//         $form = $this->createForm(FlowerFormType::class, $flower);
//         $form->handleRequest($request);

//         if ($form->isSubmitted() && $form->isValid()) {

//             // Обработка изображения
//             $imageFile = $form->get('image')->getData();

//             if ($imageFile) {
//                 // Генерация уникального имени для файла
//                 $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
//                 $safeFilename = $slugger->slug($originalFilename);
//                 $newFilename = uniqid() . '.' . $imageFile->guessExtension();

//                 // Перемещение файла в директорию
//                 try {
//                     $file = new File();
//                     $file->setName($newFilename);
//                     $file->setExtension($imageFile->guessExtension());
//                     $file->setStatus(1);

//                     $imageFile->move(
//                         $this->getParameter('images_directory'),
//                         $newFilename
//                     );

//                     // Сохранение файла
//                     $this->em->persist($file);
//                     $this->em->flush();

//                     // Связывание изображения с цветком
//                     $flower->setFileId($file->getId());
//                 } catch (FileException $e) {
//                     // Обработка ошибок
//                 }
//             }

//             $this->em->persist($flower);
//             $this->em->flush();
//             $this->addFlash('success', 'Цветок успешно добавлен!');

//             $assortment = new Storage;
//             $assortment->setFlowerId($flower->getId());
//             $assortment->setAmount(0);            
//             $this->em->persist($assortment);
//             $this->em->flush();

//             return $this->redirectToRoute('flower_list');
//         }

//         return $this->render('admin/flowers.html.twig', [
//             'flowers' => $flowers,
//             'form' => $form->createView(),
//         ]);
//     }

//     // Страница просмотра заказов
//     #[Route(path: '/admin/orders', name: 'order_list')] 
//     function orders(Request $request) {
//         dump($request->getSession()->getId());
//         $orders = $this->em->getRepository(Order::class)->findAll();
//         $flowers = $this->em->getRepository(Flower::class)->findAll();
//         $flowerNames = [];
//         foreach ($flowers as $flower) {
//             $flowerNames[$flower->getId()] = $flower->getName();
//         }
        
//         return $this->render('admin/orders.html.twig', [
//             'orders' => $orders,
//             'flowerNames' => $flowerNames,
//         ]);
//     }

//     // Страница редактирования склада
//     #[Route(path: '/admin/storage', name: 'storage_list')] 
//     function storage(Request $request) {
//         dump($request->getSession()->getId());
//         // Добавление записи
//         $assortment = new Storage();
//         $form = $this->createForm(StorageFormType::class, $assortment);
//         $form->handleRequest($request);
//         if ($form->isSubmitted() && $form->isValid()) {
//             $flower = $form->get('flowerId')->getData();
//             $assortment = $this->em->getRepository(Storage::class)->findOneBy(['flower_id' => $flower->getId()]);
//             $amount = $assortment->getAmount() + $form->get('amount')->getData();
//             $assortment->setAmount($amount);
//             $assortment->setFlowerId($flower->getId());
//             $this->em->persist($assortment);
//             $this->em->flush();
//             $this->addFlash('success', 'Storage record added successfully!');
//             return $this->redirectToRoute('storage_list');
//         }

//         // Вывод таблицы
//         $assortment = $this->em->getRepository(Storage::class)->findAll();
//         $flowers = $this->em->getRepository(Flower::class)->findAll();
//         $flowerNames = [];
//         foreach ($flowers as $flower) {
//             $flowerNames[$flower->getId()] = $flower->getName();
//         }

//         return $this->render('admin/storage.html.twig', [
//             'assortment' => $assortment,
//             'flowerNames' => $flowerNames,
//             'form' => $form->createView(),
//         ]);
//     }

//     #[Route('/admin/flower/delete/{id}', name: 'flower_delete', methods: ['POST'])]
//     public function delete(int $id): RedirectResponse
//     {
//         $flower = $this->em->getRepository(Flower::class)->find($id);
//         $assortment = $this->em->getRepository(Storage::class)->findOneBy(['flower_id' => $id]);
//         $file = $this->em->getRepository(File::class)->find($flower->getFileId());

//         if ($flower) {
//             $this->em->remove($flower);
//             $this->em->flush();
//             $this->em->remove($assortment);
//             $this->em->flush();
//             $this->em->remove($file);
//             $this->em->flush();
//             $this->addFlash('success', 'Flower deleted successfully.');
//         } else {
//             $this->addFlash('error', 'Flower not found.');
//         }

//         return $this->redirectToRoute('flower_list');
//     }

//     #[Route(path:"/orders/{id}/status/{status}", name:"update_order_status")]
//     public function updateStatus(int $id, int $status): Response
//     {
//         $order = $this->em->getRepository(Order::class)->find($id);

//         if (!$order) {
//             throw $this->createNotFoundException('Order not found');
//         }

//         $order->setStatus($status);
//         $this->em->flush();

//         $this->addFlash('success', 'Статус заказа успешно обновлен.');

//         return $this->redirectToRoute('order_list');
//     }

//     #[Route(path:"/order/delete/{id}", name:"delete_order")]
//     public function deleteOrder(int $id): Response
//     {
//         $order = $this->em->getRepository(Order::class)->find($id);

//         if ($order) {
//             $this->em->remove($order);
//             $this->em->flush();
//             $this->addFlash('success', 'Заказ успешно удалён.');
//         } else {
//             $this->addFlash('error', 'Заказ не найден.');
//         }

//         return $this->redirectToRoute('order_list');
//     }


// }