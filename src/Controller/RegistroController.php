<?php

namespace App\Controller;

use App\Entity\Registro;
use App\Form\EvaluacionType;
use App\Form\RegistroType;
use App\Repository\RegistroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/registro")
 */
class RegistroController extends AbstractController
{
    /**
     * @Route("/", name="app_registro_index", methods={"GET"})
     */
    public function index(RegistroRepository $registroRepository): Response
    {
        return $this->render('registro/index.html.twig', [
            'registros' => $registroRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_registro_new", methods={"GET", "POST"})
     */
    public function new(Request $request, RegistroRepository $registroRepository,\Swift_Mailer $mailer): Response
    {
        $registro = new Registro();
        $form = $this->createForm(RegistroType::class, $registro);
        $form->remove('aceptado');
        $form->remove('confirmado');
        $form->remove('comentarios');
        $form->remove('recomendacion');
        $form->remove('cartaFile');

        $twigglobals = $this->get("twig")->getGlobals();
        $nombre_evento = $twigglobals["evento"];

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $registroRepository->add($registro, true);

            // Mail
            $message = (new \Swift_Message($nombre_evento.' - Registro'))
                ->setFrom('webmaster@matmor.unam.mx')
                ->setTo(array($registro->getMail() ))
                //->setTo('gerardo@matmor.unam.mx')
                ->setBcc(array('gerardo@matmor.unam.mx'))
                ->setBody($this->renderView('mails/confirmacion.txt.twig', array('registro' => $registro)));

            $mailer->send($message);

            // Mail
            $message = (new \Swift_Message($nombre_evento.' - Carta de recomendación'))
                ->setFrom('webmaster@matmor.unam.mx')
                ->setTo(array($registro->getMailprofesor() ))
                //->setTo('gerardo@matmor.unam.mx')
                ->setBcc(array('gerardo@matmor.unam.mx'))
                ->setBody($this->renderView('mails/solcarta.txt.twig', array('registro' => $registro)));

            $mailer->send($message);


            //return $this->redirectToRoute('app_registro_index', [], Response::HTTP_SEE_OTHER);
            return $this->render('registro/confirmacion.html.twig', [
                'registro' => $registro,
            ]);
        }

        return $this->renderForm('registro/new.html.twig', [
            'registro' => $registro,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{slug}", name="app_registro_show", methods={"GET"})
     */
    public function show(Registro $registro): Response
    {
        return $this->render('registro/show.html.twig', [
            'registro' => $registro,
        ]);
    }

    /**
     * @Route("/{slug}/edit", name="app_registro_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Registro $registro, RegistroRepository $registroRepository): Response
    {
        $form = $this->createForm(RegistroType::class, $registro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registroRepository->add($registro, true);

            return $this->redirectToRoute('app_registro_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('registro/edit.html.twig', [
            'registro' => $registro,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{slug}/{mail}/recomendacion", name="app_registro_recomendacion", methods={"GET", "POST"})
     */
    public function recomendacion(Request $request, Registro $registro, RegistroRepository $registroRepository, $mail, $slug,\Swift_Mailer $mailer): Response
    {
        $form = $this->createForm(RegistroType::class, $registro);
        $form->remove('nombre');
        $form->remove('paterno');
        $form->remove('materno');
        $form->remove('sexo');
        $form->remove('nacimiento');
        $form->remove('mail');
        $form->remove('procedencia');
        $form->remove('carrera');
        $form->remove('semestre');
        $form->remove('porcentaje');
        $form->remove('profesor');
        $form->remove('promedio');
        $form->remove('univprofesor');
        $form->remove('mailprofesor');
        $form->remove('confirmado');
        $form->remove('comentarios');
        $form->remove('restricciones');
        $form->remove('beca');
        $form->remove('aceptado');
        $form->remove('razones');
        $form->remove('curso1');
        $form->remove('curso2');
        $form->remove('credencialFile');

        $twigglobals = $this->get("twig")->getGlobals();
        $nombre_evento = $twigglobals["evento"];

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registro->setModified(new \DateTime());

            $registroRepository->add($registro, true);

            // Mail
            $message = (new \Swift_Message($nombre_evento.' - Carta de recomendación'))
                ->setFrom('webmaster@matmor.unam.mx')
                ->setTo(array($registro->getMailprofesor() ))
                //->setTo('gerardo@matmor.unam.mx')
                ->setBcc(array('gerardo@matmor.unam.mx'))
                ->setBody($this->renderView('mails/confirmacion_carta.txt.twig', array('registro' => $registro)));

            $mailer->send($message);

//            return $this->redirectToRoute('app_registro_index', [], Response::HTTP_SEE_OTHER);
            return $this->render('registro/confirmacion_carta.html.twig', [
                'registro' => $registro,
            ]);
        }

        if( $registro->getMail() == $registro->getMailprofesor() ||  $mail != $registro->getMail() || $slug != $registro->getSlug()){

            throw $this->createNotFoundException('Existe algún problema con la información de registro favor de contactar a webmaster@matmor.unam.mx');
        }

        if( $registro->getCartaName() != null || $registro->getRecomendacion() != null)
        {
            return $this->render('registro/confirmacion_carta.html.twig', [
                'registro' => $registro,
            ]);
        }


        return $this->renderForm('registro/recomendacion.html.twig', [
            'registro' => $registro,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{slug}/evaluacion", name="app_registro_evaluacion", methods={"GET", "POST"})
     */
    public function evaluacion(Request $request, Registro $registro, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EvaluacionType::class, $registro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($registro);
            $entityManager->flush();

            return $this->redirectToRoute('app_registro_show', ['slug'=>$registro->getSlug()], Response::HTTP_SEE_OTHER);

        }
        $template = $request->isXmlHttpRequest() ? '_evalua.html.twig' : 'show.html.twig';


        return $this->renderForm('registro/'.$template, [
            'registro' => $registro,
            'form' => $form,
        ]);
    }


    /**
     * @Route("/{id}", name="app_registro_delete", methods={"POST"})
     */
    public function delete(Request $request, Registro $registro, RegistroRepository $registroRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$registro->getId(), $request->request->get('_token'))) {
            $registroRepository->remove($registro, true);
        }

        return $this->redirectToRoute('app_registro_index', [], Response::HTTP_SEE_OTHER);
    }






}
