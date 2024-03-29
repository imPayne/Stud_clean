<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Housework;
use App\Entity\Participant;
use App\Entity\Service;
use App\Form\MenagePartyFormType;
use App\Repository\CustomerRepository;
use App\Repository\HouseworkRepository;
use App\Repository\ParticipantRepository;
use App\Repository\ServiceRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

class HouseworkController extends AbstractController
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \Exception
     */
    #[Route('/housework', name: 'app_housework')]
    public function index(ServiceRepository $serviceRepository, FileUploader $fileUploader, Request $request, CustomerRepository $customerRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return ($this->redirectToRoute('app_login'));
        }
        $newHousework = new Housework();
        $newParticipant = new Participant();

        $form = $this->createForm(MenagePartyFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $customer = $customerRepository->findOneBy(['email' => $user->getUserIdentifier()]);
            $participantForm = $form->get('Participant');

            if ($customer) {
                $newHousework->setDateStart($form->get('dateStart')->getData());
                $newHousework->setDescription($form->get('description')->getData());
                $newHousework->setTitle($form->get('title')->getData());
                $image = $form->get('listImage')->getData();
                $fileName = $fileUploader->upload($image);
                $newHousework->setListImage($fileName);
                $date = $form->get('dateStart')->getData();
                $newHousework->setDateStart($date);
                $newHours = $form->get('hours')->getData();
                $newHousework->setHour(new \DateTime($newHours));
                $newHousework->setPrice($form->get('price')->getData());

                $serviceChoosen = $participantForm->get('service')->getData();
                $newParticipant->setService($serviceChoosen);
                $newParticipant->setHousework($newHousework);
                $newHousework->addParticipant($newParticipant);
                $entityManager->persist($newParticipant);

                $customer->addHousework($newHousework);

                $entityManager->persist($customer);
                $entityManager->persist($newHousework);
                $entityManager->flush();

                return $this->redirectToRoute("app_profile");
            }
        }

        return $this->render('housework/index.html.twig', [
            'controller_name' => 'HouseworkController',
            'form' => $form->createView(),
        ]);
    }
    #[Route('/my_houseworks', name: 'app_show_houseworks')]
    public function myHouseworks(CustomerRepository $customerRepository, ParticipantRepository $participantRepository,  HouseworkRepository $houseworkRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return ($this->redirectToRoute('app_login'));
        }
        $customer = $customerRepository->findOneBy(['email' => $this->getUser()]);

        return $this->render('housework/myHouseworks.html.twig', [
            'controller_name' => 'HouseworkController',
            'customer' => $customer,
        ]);
    }
}
