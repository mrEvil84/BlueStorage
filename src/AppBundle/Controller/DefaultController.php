<?php

declare(strict_types =1);

namespace AppBundle\Controller;

use Blue\StorageBundle\Command\CreateProductCommand;
use Blue\StorageBundle\Command\DeleteProductCommand;
use Blue\StorageBundle\Command\UpdateProductCommand;
use Blue\StorageBundle\Entity\Product;
use Blue\StorageBundle\Exceptions\AddProductException;
use Blue\StorageBundle\Exceptions\DeleteProductException;
use Blue\StorageBundle\Exceptions\UpdateProductException;
use Blue\StorageBundle\Query\ProductQueryFactory;
use Blue\StorageBundle\Service\ProductService;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends FOSRestController
{
    /**
     * @Rest\Get("/product"),
     * @Rest\QueryParam(name="page", requirements="\d+", default=0, description="Results page")
     * @Rest\QueryParam(name="perPage", requirements="\d+", default=10, description="Results page")
     * @Rest\QueryParam(name="search", requirements="(existing|non_existing|min_amount)", allowBlank=false, default="existing", description="Search for existing or non existing products")
     * @Rest\QueryParam(name="order", requirements="(asc|desc)", allowBlank=false, default="desc", description="Sort order")
     * @param ParamFetcher $paramFetcher
     * @return JsonResponse
     */
    public function getProduct(ParamFetcher $paramFetcher) : JsonResponse
    {
        try {
            $productQuery = ProductQueryFactory::getProductQuery(
                $paramFetcher->get('search'),
                $paramFetcher->get('order'),
                (int)$paramFetcher->get('page'),
                (int)$paramFetcher->get('perPage')
            );

            $productService = $this->getProductService();
            $products = $productService->getProduct($productQuery);

            return $this->json($products);
        } catch (ServiceCircularReferenceException $exception) {
            return $this->json($exception->getMessage(), 400);
        } catch (\Throwable $exception) {
            return $this->json($exception->getMessage(), 400);
        } catch (FatalThrowableError $exception) {
            return $this->json($exception->getMessage(), 400);
        }
    }

    /**
     * @Rest\Patch("/product/{id}")
     * @Rest\RequestParam(name="name")
     * @Rest\RequestParam(name="amount")
     * @param ParamFetcher $paramFetcher
     * @param string $id
     * @return JsonResponse
     */
    public function updateProduct(ParamFetcher $paramFetcher, $id)
    {
        try {
            $updateProductCommand = new UpdateProductCommand(
                (int)$id,
                $paramFetcher->get('name'),
                $paramFetcher->get('amount')
            );
            $productService = $this->getProductService();
            $productService->updateProduct($updateProductCommand);

            return $this->json('updated');
        } catch (UpdateProductException $exception) {
            return $this->json($exception->getMessage(), 400);
        } catch (\Throwable $exception) {
            return $this->json($exception->getMessage(), 400);
        } catch (FatalThrowableError $exception) {
            return $this->json($exception->getMessage(), 400);
        }
    }

    /**
     * @Rest\Post("/product")
     * @Rest\RequestParam(name="name")
     * @Rest\RequestParam(name="amount")
     * @param ParamFetcher $paramFetcher
     * @return JsonResponse
     */
    public function addProduct(ParamFetcher $paramFetcher)
    {
        try {
            $createProductCommand = new CreateProductCommand(
                Product::NULL_ID,
                $paramFetcher->get('name'),
                $paramFetcher->get('amount')
            );
            $productService = $this->getProductService();
            $productService->addProduct($createProductCommand);

            return $this->json('added');
        } catch (AddProductException $exception) {
            return $this->json($exception->getMessage(), 400);
        } catch (\Throwable $exception) {
            return $this->json($exception->getMessage(), 400);
        } catch (FatalThrowableError $exception) {
            return $this->json($exception->getMessage(), 400);
        }
    }

    /**
     * @Rest\Delete("/product/{id}")
     * @param string $id
     * @return JsonResponse
     * @internal param ParamFetcher $paramFetcher
     */
    public function deleteProduct($id) : JsonResponse
    {
        try {
            $deleteProductCommand = new DeleteProductCommand(
                (int)$id,
                Product::NULL_NAME,
                Product::NULL_AMOUNT
            );

            $productService = $this->getProductService();
            $productService->deleteProduct($deleteProductCommand);

            return $this->json('deleted');
        } catch (DeleteProductException $exception) {
            return $this->json($exception->getMessage(), 400);
        } catch (\Throwable $exception) {
            return $this->json($exception->getMessage(), 400);
        } catch (FatalThrowableError $exception) {
            return $this->json($exception->getMessage(), 400);
        }
    }

    /**
     * @return ProductService|object
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    private function getProductService()
    {
        return $this->container->get('product.service');
    }
}
