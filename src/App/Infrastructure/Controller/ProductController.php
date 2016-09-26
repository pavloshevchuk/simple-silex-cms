<?php

namespace App\Infrastructure\Controller;

use App\Domain\Entity\ProductEntity;
use App\Infrastructure\Application;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ProductController
 *
 * @package App\Infrastructure\Controller
 */
class ProductController
{
    /**
     * @param Request $request
     * @param Application $app
     * @return string
     */
    public function productCreateAction(Request $request, Application $app)
    {
        $form = $this->getProductForm($app['form.factory']);
        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var EntityManager $entityManager */
            $entityManager = $app['orm.ems']['mysql'];

            $data = $form->getData();
            $productEntity = new ProductEntity();
            $productEntity->setTitle($data['title']);
            $productEntity->setDescription($data['description']);
            $productEntity->setCreated(new \DateTime());
            $productEntity->setUpdated(new \DateTime());
            $entityManager->persist($productEntity);
            $entityManager->flush();

            $app['session']->getFlashBag()
                           ->add('message', 'Product has been successfully created.');

            return $app->redirect($app->url('product.read.all'));
        }

        return $app['twig']->render('product.create.twig', array(
            'title' => 'Create a new product',
            'form' => $form->createView(),
            'action' => 'Create',
        ));
    }

    /**
     * @param $productId
     * @param Request $request
     * @param Application $app
     * @return mixed
     */
    public function productReadAction($productId, Request $request, Application $app)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $app['orm.ems']['mysql'];

        $productEntity = $entityManager->getRepository('App\Domain\Entity\ProductEntity')
                                       ->findOneBy(array('product_id' => $productId));

        if ($productEntity instanceof ProductEntity) {
            return $app['twig']->render('product.read.twig', array(
                'product' => array(
                    'id' => $productEntity->getProductId(),
                    'title' => $productEntity->getTitle(),
                    'description' => $productEntity->getDescription(),
                    'created' => $productEntity->getCreated()->format('r'),
                    'updated' => $productEntity->getUpdated()->format('r'),
                ),
            ));
        }

        return $app->abort(404, "Product $productId does not exist.");
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return string
     */
    public function productUpdateAction($productId, Request $request, Application $app)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $app['orm.ems']['mysql'];

        /** @var ProductEntity $productEntity */
        $productEntity = $entityManager->getRepository('App\Domain\Entity\ProductEntity')
                                       ->findOneBy(array('product_id' => $productId));

        if (empty($productEntity)) {
            return $app->abort(404, "Product $productId does not exist.");
        }

        $form = $this->getProductForm($app['form.factory'], array(
            'title' => $productEntity->getTitle(),
            'description' => $productEntity->getDescription(),
        ));
        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var EntityManager $entityManager */
            $entityManager = $app['orm.ems']['mysql'];

            $data = $form->getData();
            $productEntity = $entityManager->getRepository('App\Domain\Entity\ProductEntity')
                                           ->findOneBy(array('product_id' => $productId));

            $productEntity->setTitle($data['title']);
            $productEntity->setDescription($data['description']);
            $productEntity->setUpdated(new \DateTime());
            $entityManager->persist($productEntity);
            $entityManager->flush();

            $app['session']->getFlashBag()
                           ->add('message', 'Product has been successfully updated.');

            return $app->redirect($app->url('product.read.all'));
        }

        return $app['twig']->render('product.create.twig', array(
            'title' => 'Update',
            'form' => $form->createView(),
            'action' => 'Update',
        ));
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return string
     */
    public function productReadAllAction(Request $request, Application $app)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $app['orm.ems']['mysql'];

        $products = $entityManager->getRepository('App\Domain\Entity\ProductEntity')
                                  ->findAll();

        $results = array();

        if (!empty($products)) {
            /** @var ProductEntity $product */
            foreach ($products as $product) {
                $results[] = array(
                    'id' => $product->getProductId(),
                    'title' => $product->getTitle(),
                    'created' => $product->getCreated()->format('r'),
                    'updated' => $product->getUpdated()->format('r'),
                );
            }
        }

        return $app['twig']->render('product.read.all.twig', array(
            'products' => $results,
        ));
    }

    /**
     * @param FormFactory $formFactory
     * @param array $data
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    private function getProductForm(FormFactory $formFactory, array $data = array())
    {
        /** @var FormBuilder $formBuilder */
        $formBuilder = $formFactory->createBuilder('form', $data);

        $formBuilder->add('title', 'text', array(
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array('min' => 5)),
            ),
        ));

        $formBuilder->add('description', 'textarea', array(
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array('min' => 10)),
            ),
        ));

        $formBuilder->add('create', 'submit');

        return $formBuilder->getForm();
    }
}
