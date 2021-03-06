<?php

namespace Oro\Bundle\GridBundle\Action\MassAction;

interface MassActionInterface
{
    /**
     * Mass action name
     *
     * @return string
     */
    public function getName();

    /**
     * ACL resource name
     *
     * @return string|null
     */
    public function getAclResource();

    /**
     * Mass action label
     *
     * @return string|null
     */
    public function getLabel();

    /**
     * Action options (route, ACL resource etc.)
     *
     * @return array
     */
    public function getOptions();

    /**
     * Get specific option by name
     *
     * @param string $name
     * @return mixed
     */
    public function getOption($name);
}
