<?php
namespace CFX\Persistence;

abstract class AbstractDataContext implements DataContextInterface {
    /**
     * Cache of child datasources
     */
    protected $datasources = [];




    /**
     * Convenience method for turning datasource "getter" methods into read-only properties
     */
    public function __get($name) {
        return $this->getNamedDatasource($name);
    }

    /**
     * @see DataContextInterface::datasourceForType
     */
    public function datasourceForType($jsonApiType) {
        // Convert from dash-case to camelCase
        $type = explode('-', $jsonApiType);
        for($i = 1; $i < count($type); $i++) $type[$i] = ucfirst($type[$i]);
        $type = implode('', $type);

        // Try to return a datasource with that name
        return $this->getNamedDatasource($type);
    }

    /**
     * @see DataContextInterface::newResource
     */
    public function newResource($data=null, $type=null, $validAttrs=null, $validRels=null) {
        try {
            return $this->datasourceForType($type)->create($data, $validAttrs, $validRels);
        } catch (UnknownDatasourceException $e) {
            throw new UnknownResourceTypeException("Type `$type` is unknown. You can handle this type by adding a valid client for it to your DataContext.");
        }
    }

    /**
     * @see DataContextInterface::convertResource
     */
    public function convertResource(\CFX\JsonApi\ResourceInterface $src, $conversionType) {
        try {
            $datasource = $this->datasourceForType($src->getResourceType());
            return $datasource->convert($src, $conversionType);
        } catch (UnknownDatasourceException $e) {
            throw new UnknownResourceTypeException("Programmer: You've tried to convert a resource of type `{$src->getResourceType()}` to it's `$conversionType` format, but this data context (`".get_class($this)."`) doesn't know how to handle `{$src->getResourceType()}`-type resources.");
        }
    }




    /**
     * getNamedDatasource -- internal method for getting a datasource by name
     *
     * This method is meant to be the back-end for several different front-end methods of getting a datasource (hence
     * its protected visibility)
     */
    protected function getNamedDatasource($name) {
        if (!array_key_exists($name, $this->datasources)) $this->datasources[$name] = $this->instantiateDatasource($name);
        return $this->datasources[$name];
    }

    /**
     * Instantiate a datasource with the given `$name`
     *
     * This is a factory method for instantiating datasources of various types. It can be overridden in child contexts to
     * provide arbitrary datasources
     */
    protected function instantiateDatasource($name) {
        throw new UnknownDatasourceException("Programmer: Don't know how to handle datasources of type `$name`. If you'd like to handle this, you should either add this datasource to the `instantiateDatasource` method in this class or create a derivative class to which to add it.");
    }
}

