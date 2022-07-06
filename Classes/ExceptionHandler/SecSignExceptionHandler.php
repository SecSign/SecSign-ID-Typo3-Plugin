<?php

namespace SecSign\Secsign\ExceptionHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SecSignExceptionHandler implements PageErrorHandlerInterface
{

    /**
     * @param ServerRequestInterface $request
     * @param string $message
     * @param array $reasons
     * @return ResponseInterface
     */
    public function handlePageError(
        ServerRequestInterface $request,
        string $message,
        array $reasons = []
    ): ResponseInterface {

        //check whether user is logged in
        
        return new RedirectResponse('/?returnURL=' . $request->getUri()->getPath(), 403);
    }

}