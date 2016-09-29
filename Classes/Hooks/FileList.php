<?php
/**
 * Extends the file list
 *
 * @package Focuspoint\Hooks
 * @author  Tim Lochmüller
 */

namespace HDNET\Focuspoint\Hooks;

use HDNET\Focuspoint\Service\WizardService;
use HDNET\Focuspoint\Utility\FileUtility;
use HDNET\Focuspoint\Utility\ImageUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\FileListEditIconHookInterface;

/**
 * Extends the file list
 *
 * @author Tim Lochmüller
 * @hook   TYPO3_CONF_VARS|SC_OPTIONS|fileList|editIconsHook
 */
class FileList implements FileListEditIconHookInterface
{

    /**
     * Modifies edit icon array
     *
     * @param array $cells Array of edit icons
     * @param \TYPO3\CMS\Filelist\FileList $parentObject Parent object
     *
     * @return void
     */
    public function manipulateEditIcons(&$cells, &$parentObject)
    {
        /** @var WizardService $wizardService */
        $wizardService = GeneralUtility::makeInstance(WizardService::class);

        try {
            $metaUid = $this->getFileMetaUidByCells($cells);
            $file = FileUtility::getFileByMetaData($metaUid);
        } catch (\Exception $ex) {
            $cells['focuspoint'] = $wizardService->getWizardButton();
            return;
        }

        if (!ImageUtility::isValidFileExtension($file->getExtension())) {
            $cells['focuspoint'] = $wizardService->getWizardButton();
            return;
        }

        $wizardArguments = [
            'P' => [
                'metaUid' => $metaUid,
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
            ],
        ];
        $wizardUri = BackendUtility::getModuleUrl('focuspoint', $wizardArguments);
        $cells['focuspoint'] = $wizardService->getWizardButton($wizardUri);
    }

    /**
     * Get the file object of the given cell information
     *
     * @param array $cells
     *
     * @return int
     * @throws \Exception
     */
    protected function getFileMetaUidByCells($cells)
    {
        if (GeneralUtility::compat_version('7.2.0')) {
            if ($cells['__fileOrFolderObject'] instanceof FileInterface) {
                $metaData = $cells['__fileOrFolderObject']->_getMetaData();
            }
            if (!isset($metaData['uid'])) {
                throw new \Exception('No meta data found', 1475144024);
            }
            return (int)$metaData['uid'];
        } else {
            $pattern = '/sys_file_metadata\]\[([0-9]*)\]/';
            if (!preg_match($pattern, $cells['editmetadata'], $matches)) {
                throw new \Exception('No valid metadata information found', 1475144025);
            }
            return (int)$matches[1];
        }
    }
}
