<?php

namespace SecSign\Secsign\UserFunctions\FormEngine;


use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class SecSignIDEval
{

    /**
    * JavaScript code for client side validation/evaluation
    *
    * @return string JavaScript code for client side validation/evaluation
    */
   public function returnFieldJS()
   {
       
     return 'return value;';
   }

   /**
    * Server-side validation/evaluation on saving the record
    *
    * @param string $value The field value to be evaluated
    * @param string $is_in The "is_in" value of the field configuration from TCA
    * @param bool $set Boolean defining if the value is written to the database or not.
    * @return string Evaluated field value
    */
   public function evaluateFieldValue($value, $is_in, &$set)
   {
       if($_POST['data']['be_users'])
       {
           if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG){error_log('Changed secsignid for BEUser');}
               
            $userArray=array_pop(array_reverse($_POST['data']['be_users']));
            $username=$userArray['username'];

            $usernameForSecSignIDFromDB= \SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignIDBE('username', $value);
            
            if ($usernameForSecSignIDFromDB && $usernameForSecSignIDFromDB!==$username && $value!=='')
            {
                $set=0;
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    'ID already used by another user',
                    'ID already used',
                    FlashMessage::ERROR,
                    true
                );

                $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
                $flashMessageService = $objectManager->get(FlashMessageService::class);
                $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $messageQueue->addMessage($message);
                return null;
            }else{
                $secsignidFromDB=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('secsignid', $username);
                if($secsignidFromDB!==$value)
                {
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('activeMethods', '', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('lastMethod', '', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', '', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', '', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $username);

                }
                return $value;
            }
       }else{
            if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG){error_log('Changed secsignid for BEUser');}
            $userArray=array_pop(array_reverse($_POST['data']['fe_users']));
            $username=$userArray['username'];

            $usernameForSecSignIDFromDB= \SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignID('username', $value);
            error_log($usernameForSecSignIDFromDB);
            if ($usernameForSecSignIDFromDB && $usernameForSecSignIDFromDB!==$username && $value!=='')
            {
                $set=0;
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    'ID already used by another user',
                    'ID already used',
                    FlashMessage::ERROR,
                    true
                );

                $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
                $flashMessageService = $objectManager->get(FlashMessageService::class);
                $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $messageQueue->addMessage($message);
                return null;
            }else{
                $secsignidFromDB=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('secsignid', $username);
                if($secsignidFromDB!==$value)
                {
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('activeMethods', '', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('lastMethod', '', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', '', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', '', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $username);

                }
                return $value;
            }
       }
   }

   /**
    * Server-side validation/evaluation on opening the record
    *
    * @param array $parameters Array with key 'value' containing the field value from the database
    * @return string Evaluated field value
    */
   public function deevaluateFieldValue(array $parameters)
   {
       
      return $parameters['value'];
   }
}
