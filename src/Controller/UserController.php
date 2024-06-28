<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use App\Repository\UserRepository;
use App\Utils\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/user', name: 'user_')]
class UserController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function profile(Request                     $request,
                            UserPasswordHasherInterface $userPasswordHasher,
                            EntityManagerInterface      $entityManager,
                            FileUploader                $fileUploader): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('outing_list');
            }

            if (!empty($form->get('plainPassword')->getData())) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }

            if (!empty($form->get('image')->getData())) {
                $file = $form->get('image')->getData();
                $newFilename = $fileUploader->upload($file, $this->getParameter('profile_image_directory'), $user->getPseudo());
                $user->setImage($newFilename);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour !');
            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/user.profile.html.twig', [
            'profileForm' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Request $request,
                         UserRepository $userRepository,
                         int     $id = null): Response
    {
        if ($id === null) {
            throw $this->createNotFoundException('Page non trouvée.');
        } else {
            $user = $userRepository->find($id);
        }

        return $this->render('user/user.show.html.twig', [
            'user' => $user,
        ]);
    }
}
