<?php

namespace cv6\Core\Cli\Command;

trait cv6CommandTrait
{
    /**
     * @return string
     */
    abstract function getInternalShortCode();

    public function configure()
    {
        parent::configure();

        $shortCode = $this->getInternalShortCode();
        $rebuildName = $this->getRebuildName();

        $this->setName('dd-' . $shortCode . ':' . $rebuildName);
        $this->setAliases(['cv6:' . $shortCode . ':' . $rebuildName]);
    }
}