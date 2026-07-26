<?php

namespace cv6\Core\Admin\Controller;

use cv6\Core\Helper\Icon as IconHelper;
use XF\Admin\Controller\AbstractController;

class Icon extends AbstractController
{
    public function actionAutoComplete()
    {
        $q = $this->filter('q', 'str');

        $results = IconHelper::searchIcons($q);

        $viewParams = [
            'results' => $results
        ];

        $reply = $this->view('cv6\Core:Icon\AutoComplete', '', $viewParams);
        $reply->setJsonParam('results', $results);
        return $reply;
    }
}
