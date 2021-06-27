<?php


namespace Hamaelt\ZipValidator;

use finfo;
use Hamaelt\ZipValidator\Exception\InvalidMimeTypeException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Mockery\Exception;
use ZipArchive;
use Hamaelt\ZipValidator\MimeTypes;

class Validator
{
    /**
     * @var array
     */
    private array $allowedMimeTypes;

    /**
     * @var finfo
     */
    private finfo $finfo;

    /**
     * @var ZipArchive
     */
    private ZipArchive $zip;

    /**
     * Validator constructor.
     *
     * @param string $fileTypes
     */
    public function __construct(string $fileTypes)
    {
        $this->finfo = new finfo(FILEINFO_MIME_TYPE);
        $this->zip = new ZipArchive();
        try {
            $this->allowedMimeTypes = $this->getMimeTypes($fileTypes);
        } catch (\Exception $exception) {
            throw new \Exception('Invalid MimeType provided');
        }
    }


    /**
     * Validates ZIP content with given file path.
     *
     * @param string $filePath
     *
     * @return Collection
     */
    public function validate(string $filePath): Collection
    {
        $zipContent = $this->readContent($filePath);

        return $this->files
            ->reject(function ($value, $key) use ($zipContent) {
                return $this->checkFile($zipContent, $value, $key);
            })->map(function ($value, $key) {
                return is_int($key) ? $value : $key;
            });
    }


    /**
     * Reads ZIP file content and returns collection with result.
     *
     * @param UploadedFile $file
     * @return bool
     */
    public function validateZipFile(UploadedFile $file): bool
    {
        $this->zip->open($file->getPathname());
        for ($i = 0; $i < $this->zip->count(); $i++) {
            if (!in_array($this->finfo->buffer($this->zip->getFromIndex($i)), $this->allowedMimeTypes)) {
                $this->mimeType = $this->finfo->buffer($this->zip->getFromIndex($i));
                $this->zip->close();
                return false;
            }
        }
        return true;
    }

    private function getMimeTypes(string $fileTypes): array
    {
        $types = explode('|', $fileTypes);
        foreach ($types as $type) {
            $this->allowedMimeTypes[] = MimeTypes::$mimeTypes[$type];
        }
        $this->allowedMimeTypes[] = 'application/octet-stream';

        return $this->allowedMimeTypes;
    }
}