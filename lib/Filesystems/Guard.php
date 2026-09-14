<?php
/**
 * @author Drajat Hasan
 * @email drajathasan20@gmail.com
 * @create date 2022-11-01 12:06:21
 * @modify date 2023-01-01 21:26:13
 * @license GPLv3
 * @desc [description]
 */

namespace SLiMS\Filesystems;

use League\MimeTypeDetection\ExtensionMimeTypeDetector;

trait Guard
{
    /**
     * Check if mime is allowed or not
     *
     * @param array $allowedMime
     * @return boolean
     */
    public function isMimeAllowed(array $allowedMime = [])
    {
        if (!$this->uploadStatus) return false;
        
        $detector = new ExtensionMimeTypeDetector();
        $this->uploadStatus = in_array($detector->detectMimeTypeFromPath($this->path . $this->uploadedFile), ($allowedMime ? $allowedMime : config('mimetype')));

        if (!$this->uploadStatus) $this->error = str_replace('{mime}', $detector->detectMimeTypeFromPath($this->path . $this->uploadedFile), __('Mime {mime} is not allowed!'));

        return $this->uploadStatus;
    }

    /**
     * Check if extension is allowed or not
     *
     * @param array $allowedExtension
     * @return boolean
     */
    public function isExtensionAllowed(array $allowedExtension = [])
    {
        if (!$this->uploadStatus) return false;

        $this->uploadStatus = in_array($this->getExt($this->path . $this->uploadedFile)??'', ($allowedExtension ? $allowedExtension : config('allowed_file_att')));

        if (!$this->uploadStatus) $this->error = str_replace('{extension}', $this->getExt($this->path . $this->uploadedFile), __('Extension {extension} is not allowed!'));

        return $this->uploadStatus;
    }

    /**
     * File size limit check
     *
     * @param int|string $maxSize
     * @return boolean
     */
    public function isLimitExceeded($maxSize)
    {
        if (!$this->uploadStatus) return false;
        
        $this->uploadStatus = $maxSize > $this->getSize($this->uploadedFile);

        if (!$this->uploadStatus) $this->error = str_replace(['{fileSize}','{maxSize}'], [$this->toUnitSize($this->getSize($this->uploadedFile)), $this->toUnitSize($maxSize)], __('Size {fileSize} greater than {maxSize}.'));

        return $this->uploadStatus;
    }

    /**
     * Scan the uploaded file for viruses/malware using ClamAV (clamdscan).
     * Fails closed: if the scanner itself is unavailable or errors out,
     * the upload is rejected rather than silently skipping the scan.
     *
     * @return boolean
     */
    public function isVirusFree()
    {
        if (!$this->uploadStatus) return false;

        $filePath = $this->path . $this->uploadedFile;
        $output = [];
        $returnCode = null;
        exec('clamdscan --no-summary --infected ' . escapeshellarg($filePath) . ' 2>&1', $output, $returnCode);

        if ($returnCode === 1) {
            // 1 = infected file found
            error_log('Virus scan REJECTED upload: ' . $filePath . ' - ' . implode(' ', $output));
            $this->uploadStatus = false;
            $this->error = __('The uploaded file was flagged as a security threat and was rejected.');
        } else if ($returnCode !== 0) {
            // anything other than 0 (clean) or 1 (infected) means the
            // scanner itself failed - reject rather than allow unscanned files through
            error_log('Virus scan FAILED to run (fail-closed, upload rejected): ' . $filePath . ' - ' . implode(' ', $output));
            $this->uploadStatus = false;
            $this->error = __('Could not verify the file is safe. Please try again or contact the administrator.');
        }

        return $this->uploadStatus;
    }

    /**
     * File image check
     * @return boolean
     */
    public function isImageFile()
    {
        if (!$this->uploadStatus) return false;
        $this->uploadStatus = exif_imagetype($this->path.$this->uploadedFile);
        if (!$this->uploadStatus) {
            $this->error =  __('Wrong image filetype.');
        }
        return $this->uploadStatus;
    }

    /**
     * get upload status
     *
     * @return boolean
     */
    public function isValid()
    {
        return $this->uploadStatus;
    }

    /**
     * Destroy uploaded file
     * if file have invalid condition
     *
     * @return void
     */
    public function destroyIfFailed()
    {
        if (!$this->uploadStatus) $this->filesystem->delete($this->uploadedFile);
    }
}
