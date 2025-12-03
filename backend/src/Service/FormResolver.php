<?php

declare(strict_types=1);

namespace App\Service;

use App\Form\Interface\PostSubmitFormInterface;
use App\Service\Exception\FormException;
use Exception;
use JsonException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormResolver
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private SerializerInterface&DenormalizerInterface $serializer,
        private TranslatorInterface $translator,
    ) {
    }

    public function resolve(Request $request, string $formType, ?object $entity, array $formOptions = []): array|object
    {
        $data = $this->extractFormData($request);

        if (null === $data) {
            throw new FormException($this->translator->trans('form.generic.empty'));
        }

        return $this->resolveWithArray($data, $formType, $entity, $formOptions);
    }

    public function resolveWithArray(array $data, string $formType, ?object $entity, array $formOptions = []): array|object
    {
        $form = $this->formFactory->create($formType, $entity, $formOptions);
        $form->submit($data);

        if (!$form->isValid()) {
            $this->throwValidationException($form);
        }

        $entity = $form->getData();

        $formType = $form->getConfig()->getType()->getInnerType();
        if ($formType instanceof PostSubmitFormInterface) {
            $entity = $formType->postSubmit($form, $entity, $form->getConfig()->getOptions());
        }

        return $entity;
    }

    private function extractFormData(Request $request): ?array
    {
        if (!empty($request->request->all()) || !empty($request->files->all())) {
            return array_merge(
                $request->request->all(),
                $request->files->all(),
            );
        }

        $content = $request->getContent();

        if ('' === $content) {
            return null;
        }

        $format = $request->getContentTypeFormat() ?? 'json';

        if ('form' === $format) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, $this->translator->trans('form.generic.invalid_form_data'));
        }

        try {
            if ('json' === $format) {
                $data = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
            } else {
                $decoded = $this->serializer->deserialize($content, 'array', $format);
                $data = \is_array($decoded) ? $decoded : [$decoded];
            }

            if (!\is_array($data)) {
                throw new FormException($this->translator->trans('form.generic.invalid_form_data'));
            }

            return $data;
        } catch (HttpException $e) {
            throw $e;
        } catch (JsonException $e) {
            throw new JsonException('Invalid JSON.', $e->getCode(), $e);
        } catch (Exception $e) {
            throw new FormException($this->translator->trans('form.generic.invalid_form_data'), previous: $e);
        }
    }

    private function throwValidationException(FormInterface $form): void
    {
        $violations = [];

        foreach ($form->getErrors(true) as $error) {
            $violations[] = $error;
        }

        if (empty($violations)) {
            throw new FormException($this->translator->trans('form.generic.validation_failed'));
        }

        $errors = array_map(
            fn(FormError $error) => $this->translator->trans($error->getMessage(), $error->getMessageParameters()),
            $violations,
        );

        $violationList = new ConstraintViolationList();
        foreach ($violations as $violation) {
            if ($violation->getCause() instanceof ConstraintViolationInterface) {
                $violationList->add($violation->getCause());
            }
        }

        throw new FormException(implode('||', $errors));
    }
}
