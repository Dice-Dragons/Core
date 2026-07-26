<?php

namespace cv6\Core\XF\Service\AddOn;

class ReleaseBuilderService extends XFCP_ReleaseBuilderService
{
	protected function getExcludedDirectories()
	{
		$excluded = parent::getExcludedDirectories();
		$excluded[] = '_docs';
		$excluded[] = '_doks';

		if ($this->addOn && file_exists($this->addOn->getBuildJsonPath()))
		{
			$buildJson = $this->addOn->getBuildJson();
			if (!empty($buildJson['exclude']) && is_array($buildJson['exclude']))
			{
				foreach ($buildJson['exclude'] as $entry)
				{
					if (is_string($entry) && !in_array($entry, $excluded, true))
					{
						$excluded[] = $entry;
					}
				}
			}
		}

		return array_unique($excluded);
	}
}
