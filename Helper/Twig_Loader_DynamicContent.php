<?php

namespace MauticPlugin\MauticAdvancedTemplatesBundle\Helper;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Monolog\Logger;
use Twig\Error\Error;
use Twig\Source;
use Twig\Loader\LoaderInterface;

class Twig_Loader_DynamicContent implements LoaderInterface
{
    private static $NAME_PREFIX = 'dc:';

    /**
     * @var ModelFactory
     */
    private $modelFactory;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Twig_Loader_DynamicContent constructor.
     * @param Logger $logger
     * @param ModelFactory $modelFactory
     */
    public function __construct(Logger $logger, ModelFactory $modelFactory)
    {
        $this->modelFactory = $modelFactory;
        $this->logger = $logger;
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @throws LoaderError When $name is not found
     */
    public function getCacheKey(string $name): string
    {
        return $name;
    }

    /**
     * @param int $time Timestamp of the last modification time of the cached template
     *
     * @throws LoaderError When $name is not found
     */
    public function isFresh(string $name, int $time): bool
    {
        // TODO: Implement isFresh() method.
        $this->logger->debug('Twig_Loader_DynamicContent: Is Fresh: ' . $time . ', ' . $name);
        return false;
    }

    /**
     * Returns the source context for a given template logical name.
     *
     * @throws LoaderError When $name is not found
     */
    public function getSourceContext(string $name): Source
    {
        $dynamicContent = $this->findTemplate($this->aliasForTemplateName($name));
        if ($dynamicContent == null) {
            throw new Error('Template ' . $name . ' does not exist');
        }
        return new Source($dynamicContent->getContent(), $name);
    }

    private function aliasForTemplateName(string $name): string
    {
        return str_replace(self::$NAME_PREFIX, '', $name);
    }

    /**
     * @param $resourceAlias
     * @return null|DynamicContent
     */
    private function findTemplate($resourceAlias): string
    {
        $model = $this->modelFactory->getModel('dynamicContent');
        $result = $model->getEntities(
            [
                'filter' => [
                    'where' => [
                        [
                            'col' => 'e.name',
                            'expr' => 'eq',
                            'val' => $resourceAlias,
                        ],
                        [
                            'col'  => 'e.isPublished',
                            'expr' => 'eq',
                            'val'  => 1,
                        ]
                    ]
                ],
                'ignore_paginator' => true,
            ]
        );

        if (count($result) === 0) {
            return null;
        }

        // The result array key is the dynamic content ID - So use array_keys and get the first (and only) found key
        $keys = array_keys($result);

        return $result[$keys[0]];
    }

    /**
     * Check if we have the source code of a template, given its name.
     *
     * @param string $name The name of the template to check if we can load
     *
     * @return bool If the template source code is handled by this loader or not
     */
    public function exists(string $name): bool
    {
        return $this->supports($name) && $this->findTemplate($this->aliasForTemplateName($name)) !== null;
    }

    /**
     * @param $name
     * @return bool
     */
    public function supports(string $name): bool
    {
        return strpos($name, self::$NAME_PREFIX) === 0;
    }
}
