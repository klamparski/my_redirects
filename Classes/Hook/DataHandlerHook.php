<?php
namespace KoninklijkeCollective\MyRedirects\Hook;

use KoninklijkeCollective\MyRedirects\Domain\Model\Redirect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility;

/**
 * DataHandler: Hook to update needed lookup variables
 *
 * @package KoninklijkeCollective\MyRedirects\Hook
 */
class DataHandlerHook
{

    /**
     * Safely check for redirect links and generate query hash
     *
     * @param string $type
     * @param string $table
     * @param string $id
     * @param array $row
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $reference
     */
    public function processDatamap_postProcessFieldArray($type, $table, $id, &$row, $reference)
    {
        if (!empty($type) && $table === Redirect::TABLE) {
            if (isset($row['url'])) {
                $row['url'] = ltrim($row['url'], '/');
                $row['url_hash'] = $this->getRedirectService()->generateUrlHash($row['url']);

                $configuration = $this->getObjectManager()->get(ConfigurationUtility::class)->getCurrentConfiguration('my_redirects');
                $collisions = $this->getRedirectService()->getCollisions(array_merge($reference->checkValue_currentRecord, $row));

                if ($collisions) {
                    $message = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf(
                            $GLOBALS['LANG']->sL('LLL:EXT:my_redirects/Resources/Private/Language/locallang.xlf:collisions-detected'),
                            $row['url'],
                            implode(array_column($collisions, 'url'), '\', \'')
                        ),
                        '',
                        FlashMessage::WARNING,
                        true
                    );

                    $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                    $flashMessageService->getMessageQueueByIdentifier()->enqueue($message);
                }
            }
        }
    }

    /**
     * @return \KoninklijkeCollective\MyRedirects\Service\RedirectService
     */
    protected function getRedirectService()
    {
        return $this->getObjectManager()->get(\KoninklijkeCollective\MyRedirects\Service\RedirectService::class);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected function getObjectManager()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
    }

}
