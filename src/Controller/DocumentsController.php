<?php

namespace App\Controller;

use App\Entity\Documents;
use App\Entity\Files;
use App\Form\DocumentsType;
use App\Repository\DocumentsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/")
 */
class DocumentsController extends AbstractController
{
    /**
     * @Route("/", name="documents_index", methods={"GET"})
     */
    public function index(DocumentsRepository $documentsRepository): Response
    {
        return $this->render('documents/index.html.twig', [
            'documents' => $documentsRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="documents_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $document = new Documents();
        $form = $this->createForm(DocumentsType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $files = $form->get('files')->getData();
            foreach($files as $file){
                $fichier = md5(uniqid()).'.'.$file->guessExtension();
                $file->move(
                    $this->getParameter('files_directory'),
                    $fichier
                );
            $doc = new Files();
            $doc->setTitle($fichier);
            $document->addFile($doc);

            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($document);
            $entityManager->flush();

            return $this->redirectToRoute('documents_index');
        }

        return $this->render('documents/new.html.twig', [
            'document' => $document,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="documents_show", methods={"GET"})
     */
    public function show(Documents $document): Response
    {
        return $this->render('documents/show.html.twig', [
            'document' => $document,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="documents_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Documents $document
     * @return Response
     */
    public function edit(Request $request, Documents $document): Response
    {
        $form = $this->createForm(DocumentsType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $files = $form->get('files')->getData();
            foreach($files as $file) {
                $fichier = md5(uniqid()) . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('files_directory'),
                    $fichier
                );
                $doc = new Files();
                $doc->setTitle($fichier);
                $document->addFile($doc);
            }
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('documents_index');
        }

        return $this->render('documents/edit.html.twig', [
            'document' => $document,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="documents_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Documents $document): Response
    {
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($document);
            $entityManager->flush();
        }

        return $this->redirectToRoute('documents_index');
    }

    /**
     * @Route("/delete/file{id}", name="documents_delete_file", methods={"GET","POST"})
     * @param Files $file
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteFile(Files $file, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);


        if($this->isCsrfTokenValid('delete'.$file->getId(), $data['_token'])){

            $nom = $file->getName();

            unlink($this->getParameter('files_directory').'/'.$nom);


            $em = $this->getDoctrine()->getManager();
            $em->remove($file);
            $em->flush();


            return new JsonResponse(['success' => 1]);
        }else{
            return new JsonResponse(['error' => 'Token Invalide'], 400);
        }
}
}
