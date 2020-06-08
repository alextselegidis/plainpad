<?php

namespace App\Services;

use Composer\Semver\Comparator;
use Desarrolla2\Cache\Cache;
use Desarrolla2\Cache\Adapter\NotCache;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use App\Exceptions\AutoUpdate\DownloadException;
use App\Exceptions\AutoUpdate\ParserException;

class AutoUpdateService
{
    /**
     * The latest version.
     *
     * @var string
     */
    private $latestVersion = null;

    /**
     * Updates not yet installed.
     *
     * @var array
     */
    private $updates = null;

    /**
     * Cache for update requests.
     *
     * @var \Desarrolla2\Cache\Cache
     */
    private $cache = null;

    /**
     * Logger instance.
     *
     * @var \Monolog\Logger
     */
    private $log = null;

    /**
     * Result of simulated install.
     *
     * @var array
     */
    private $simulationResults = array();

    /**
     * Temporary download directory.
     *
     * @var string
     */
    private $tempDir = '';

    /**
     * Install directory.
     *
     * @var string
     */
    private $installDir = '';

    /**
     * Update branch.
     *
     * @var string
     */
    private $branch = '';

    /**
     * Username authentication
     *
     * @var string
     */
    private $username = '';

    /**
     * Password authentication
     *
     * @var string
     */
    private $password = '';

    /*
     * Callbacks to be called when each update is finished
     *
     * @var array
     */
    private $onEachUpdateFinishCallbacks = [];

    /*
     * Callbacks to be called when all updates are finished
     *
     * @var array
     */
    private $onAllUpdateFinishCallbacks = [];

    /**
     * If curl should verify the host certificate.
     *
     * @var bool
     */
    private $sslVerifyHost = false;

    /**
     * Url to the update folder on the server.
     *
     * @var string
     */
    protected $updateUrl = 'https://example.com/updates/';

    /**
     * Version filename on the server.
     *
     * @var string
     */
    protected $updateFile = 'update.json';

    /**
     * Current version.
     *
     * @var string
     */
    protected $currentVersion = null;

    /**
     * Create new folders with this privileges.
     *
     * @var int
     */
    public $dirPermissions = 0755;

    /**
     * Update script filename.
     *
     * @var string
     */
    public $updateScriptName = '_upgrade.php';

    /**
     * No update available.
     */
    const NO_UPDATE_AVAILABLE = 0;

    /**
     * Zip file could not be opened.
     */
    const ERROR_INVALID_ZIP = 10;

    /**
     * Could not check for last version.
     */
    const ERROR_VERSION_CHECK = 20;

    /**
     * Temp directory does not exist or is not writable.
     */
    const ERROR_TEMP_DIR = 30;

    /**
     * Install directory does not exist or is not writable.
     */
    const ERROR_INSTALL_DIR = 35;

    /**
     * Could not download update.
     */
    const ERROR_DOWNLOAD_UPDATE = 40;

    /**
     * Could not delete zip update file.
     */
    const ERROR_DELETE_TEMP_UPDATE = 50;

    /**
     * Error while installing the update.
     */
    const ERROR_INSTALL = 60;

    /**
     * Error in simulated install.
     */
    const ERROR_SIMULATE = 70;

    /**
     * Create new instance
     *
     * @param string $tempDir
     * @param string $installDir
     * @param int $maxExecutionTime
     */
    public function __construct($tempDir = null, $installDir = null, $maxExecutionTime = 60)
    {
        // Init logger
        $this->log = new Logger('auto-update');
        $this->log->pushHandler(new NullHandler());

        $this->setTempDir(($tempDir !== null) ? $tempDir : __DIR__ . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR);
        $this->setInstallDir(($installDir !== null) ? $installDir : __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

        $this->latestVersion = '0.0.0';
        $this->currentVersion = '0.0.0';

        // Init cache
        $this->cache = new Cache(new NotCache());

        ini_set('max_execution_time', $maxExecutionTime);
    }

    /**
     * Set the temporary download directory.
     *
     * @param string $dir
     * @return bool
     */
    public function setTempDir($dir)
    {
        $dir = $this->addTrailingSlash($dir);

        if (!is_dir($dir)) {
            $this->log->debug(sprintf('Creating new temporary directory "%s"', $dir));

            if (!mkdir($dir, 0755, true)) {
                $this->log->critical(sprintf('Could not create temporary directory "%s"', $dir));

                return false;
            }
        }

        $this->tempDir = $dir;

        return true;
    }

    /**
     * Set the install directory.
     *
     * @param string $dir
     * @return bool
     */
    public function setInstallDir($dir)
    {
        $dir = $this->addTrailingSlash($dir);

        if (!is_dir($dir)) {
            $this->log->debug(sprintf('Creating new install directory "%s"', $dir));

            if (!mkdir($dir, 0755, true)) {
                $this->log->critical(sprintf('Could not create install directory "%s"', $dir));

                return false;
            }
        }

        $this->installDir = $dir;

        return true;
    }

    /**
     * Set the update filename.
     *
     * @param string $updateFile
     * @return $this
     */
    public function setUpdateFile($updateFile)
    {
        $this->updateFile = $updateFile;

        return $this;
    }

    /**
     * Set the update filename.
     *
     * @param string $updateUrl
     * @return $this
     */
    public function setUpdateUrl($updateUrl)
    {
        $this->updateUrl = $updateUrl;

        return $this;
    }

    /**
     * Set the update branch.
     *
     * @param string branch
     * @return $this
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * Set the cache component.
     *
     * @param \Desarrolla2\Cache\Adapter\AdapterInterface $adapter See https://github.com/desarrolla2/Cache
     * @param int $ttl Time to live in seconds
     * @return $this
     */
    public function setCache($adapter, $ttl = 3600)
    {
        $adapter->setOption('ttl', $ttl);
        $this->cache = new Cache($adapter);

        return $this;
    }

    /**
     * Set the version of the current installed software.
     *
     * @param string $currentVersion
     * @return $this
     */
    public function setCurrentVersion($currentVersion)
    {
        $this->currentVersion = $currentVersion;

        return $this;
    }

    /**
     * Set username and password for basic authentication.
     *
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function setBasicAuth($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * Set authentication header if username and password exist.
     *
     * @return null|resource
     */
    private function _useBasicAuth()
    {
        if ($this->username && $this->password) {
            return stream_context_create(array(
                'http' => array(
                    'header' => "Authorization: Basic " . base64_encode("$this->username:$this->password")
                )
            ));
        }

        return null;
    }

    /**
     * Add a new logging handler.
     *
     * @param \Monolog\Handler\HandlerInterface $handler See https://github.com/Seldaek/monolog
     * @return $this
     */
    public function addLogHandler(\Monolog\Handler\HandlerInterface $handler)
    {
        $this->log->pushHandler($handler);

        return $this;
    }

    /**
     * Get the name of the latest version.
     *
     * @return string
     */
    public function getLatestVersion()
    {
        return $this->latestVersion;
    }

    /**
     * Get an array of versions which will be installed.
     *
     * @return array
     */
    public function getVersionsToUpdate()
    {
        if (count($this->updates) > 0) {
            return array_map(function ($update) {
                return $update['version'];
            }, $this->updates);
        }

        return [];
    }

    /**
     * Get the results of the last simulation.
     *
     * @return array
     */
    public function getSimulationResults()
    {
        return $this->simulationResults;
    }

    /**
     * @return bool
     */
    public function getSslVerifyHost()
    {
        return $this->sslVerifyHost;
    }

    /**
     * @param bool $sslVerifyHost
     */
    public function setSslVerifyHost($sslVerifyHost)
    {
        $this->sslVerifyHost = $sslVerifyHost;

        return $this;
    }

    /**
     * Check for a new version
     *
     * @return int|bool
     *         true: New version is available
     *         false: Error while checking for update
     *         int: Status code (i.e. AutoUpdate::NO_UPDATE_AVAILABLE)
     * @throws DownloadException
     * @throws ParserException
     */
    public function checkUpdate()
    {
        $this->log->notice('Checking for a new update...');

        // Reset previous updates
        $this->latestVersion = '0.0.0';
        $this->updates = [];

        $versions = $this->cache->get('update-versions');

        // Create absolute url to update file
        $updateFile = $this->updateUrl . '/' . $this->updateFile;
        if (!empty($this->branch)) {
            $updateFile .= '.' . $this->branch;
        }

        // Check if cache is empty
        if ($versions === null || $versions === false) {
            $this->log->debug(sprintf('Get new updates from %s', $updateFile));

            // Read update file from update server
            if (function_exists('curl_version') && $this->_isValidUrl($updateFile)) {
                $update = $this->_downloadCurl($updateFile);

                if ($update === false) {
                    $this->log->error(sprintf('Could not download update file "%s" via curl!', $updateFile));

                    throw new DownloadException($updateFile);
                }
            } else {
                // TODO: Throw exception on error
                $update = @file_get_contents($updateFile, false, $this->_useBasicAuth());

                if ($update === false) {
                    $this->log->error(sprintf('Could not download update file "%s" via file_get_contents!', $updateFile));

                    throw new DownloadException($updateFile);
                }
            }

            // Parse update file
            $updateFileExtension = substr(strrchr($this->updateFile, '.'), 1);
            switch ($updateFileExtension) {
                case 'ini':
                    $versions = parse_ini_string($update, true);
                    if (!is_array($versions)) {
                        $this->log->error('Unable to parse ini update file!');

                        throw new ParserException;
                    }

                    $versions = array_map(function ($block) {
                        return isset($block['url']) ? $block['url'] : false;
                    }, $versions);

                    break;
                case 'json':
                    $versions = (array)json_decode($update);
                    if (!is_array($versions)) {
                        $this->log->error('Unable to parse json update file!');

                        throw new ParserException;
                    }

                    break;
                default:
                    $this->log->error(sprintf('Unknown file extension "%s"', $updateFileExtension));

                    throw new ParserException;
            }

            $this->cache->set('update-versions', $versions);
        } else {
            $this->log->debug('Got updates from cache');
        }

        if (!is_array($versions)) {
            $this->log->error(sprintf('Could not read versions from server %s', $updateFile));

            return false;
        }

        // Check for latest version
        foreach ($versions as $version => $updateUrl) {
            if (Comparator::greaterThan($version, $this->currentVersion)) {
                if (Comparator::greaterThan($version, $this->latestVersion)) {
                    $this->latestVersion = $version;
                }

                $this->updates[] = [
                    'version' => $version,
                    'url' => $updateUrl,
                ];
            }
        }

        // Sort versions to install
        usort($this->updates, function ($a, $b) {
            if (Comparator::equalTo($a['version'], $b['version'])) {
                return 0;
            }

            return Comparator::lessThan($a['version'], $b['version']) ? -1 : 1;
        });

        if ($this->newVersionAvailable()) {
            $this->log->debug(sprintf('New version "%s" available', $this->latestVersion));

            return true;
        } else {
            $this->log->debug('No new version available');

            return self::NO_UPDATE_AVAILABLE;
        }
    }

    /**
     * Check if a new version is available.
     *
     * @return bool
     */
    public function newVersionAvailable()
    {
        return Comparator::greaterThan($this->latestVersion, $this->currentVersion);
    }

    /**
     * Check if url is valid.
     *
     * @param string $url
     * @return boolean
     */
    protected function _isValidUrl($url)
    {
        return (filter_var($url, FILTER_VALIDATE_URL) !== false);
    }

    /**
     * Download file via curl.
     *
     * @param string $url URL to file
     * @return string|false
     */
    protected function _downloadCurl($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->sslVerifyHost);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->sslVerifyHost);
        $update = curl_exec($curl);

        $error = false;
        if (curl_error($curl)) {
            $error = true;
            $this->log->error(sprintf(
                'Could not download update "%s" via curl: %s!',
                $url,
                curl_error($curl)
            ));
        }
        curl_close($curl);

        if ($error === true) {
            return false;
        }

        return $update;
    }

    /**
     * Download the update
     *
     * @param string $updateUrl Url where to download from
     * @param string $updateFile Path where to save the download
     * @return bool
     * @throws DownloadException
     * @throws \Exception
     */
    protected function _downloadUpdate($updateUrl, $updateFile)
    {
        $this->log->info(sprintf('Downloading update "%s" to "%s"', $updateUrl, $updateFile));
        if (function_exists('curl_version') && $this->_isValidUrl($updateUrl)) {
            $update = $this->_downloadCurl($updateUrl);
            if ($update === false) {
                return false;
            }
        } elseif (ini_get('allow_url_fopen')) {
            // TODO: Throw exception on error
            $update = @file_get_contents($updateUrl, false, $this->_useBasicAuth());

            if ($update === false) {
                $this->log->error(sprintf('Could not download update "%s"!', $updateUrl));

                throw new DownloadException($updateUrl);
            }
        } else {
            throw new \Exception('No valid download method found!');
        }

        $handle = fopen($updateFile, 'w');
        if (!$handle) {
            $this->log->error(sprintf('Could not open file handle to save update to "%s"!', $updateFile));

            return false;
        }

        if (!fwrite($handle, $update)) {
            $this->log->error(sprintf('Could not write update to file "%s"!', $updateFile));
            fclose($handle);

            return false;
        }

        fclose($handle);

        return true;
    }

    /**
     * Simulate update process.
     *
     * @param string $updateFile
     * @return bool
     */
    protected function _simulateInstall($updateFile)
    {
        $this->log->notice('[SIMULATE] Install new version');
        clearstatcache();

        // Check if zip file could be opened
        $zip = zip_open($updateFile);
        if (!is_resource($zip)) {
            $this->log->error(sprintf('Could not open zip file "%s", error: %d', $updateFile, $zip));

            return false;
        }

        $i = -1;
        $files = [];
        $simulateSuccess = true;

        while ($file = zip_read($zip)) {
            $i++;

            $filename = zip_entry_name($file);
            $foldername = $this->installDir . dirname($filename);
            $absoluteFilename = $this->installDir . $filename;

            $files[$i] = [
                'filename' => $filename,
                'foldername' => $foldername,
                'absolute_filename' => $absoluteFilename,
            ];

            $this->log->debug(sprintf('[SIMULATE] Updating file "%s"', $filename));

            // Check if parent directory is writable
            if (!is_dir($foldername)) {
                mkdir($foldername);
                $this->log->debug(sprintf('[SIMULATE] Create directory "%s"', $foldername));
                $files[$i]['parent_folder_exists'] = false;

                $parent = dirname($foldername);
                if (!is_writable($parent)) {
                    $files[$i]['parent_folder_writable'] = false;

                    $simulateSuccess = false;
                    $this->log->warning(sprintf('[SIMULATE] Directory "%s" has to be writeable!', $parent));
                } else {
                    $files[$i]['parent_folder_writable'] = true;
                }
            }

            // Skip if entry is a directory
            if (substr($filename, -1, 1) == DIRECTORY_SEPARATOR) {
                continue;
            }

            // Read file contents from archive
            $contents = zip_entry_read($file, zip_entry_filesize($file));
            if ($contents === false) {
                $files[$i]['extractable'] = false;

                $simulateSuccess = false;
                $this->log->warning(sprintf('[SIMULATE] Coud not read contents of file "%s" from zip file!', $filename));
            }

            // Write to file
            if (file_exists($absoluteFilename)) {
                $files[$i]['file_exists'] = true;
                if (!is_writable($absoluteFilename)) {
                    $files[$i]['file_writable'] = false;

                    $simulateSuccess = false;
                    $this->log->warning(sprintf('[SIMULATE] Could not overwrite "%s"!', $absoluteFilename));
                }
            } else {
                $files[$i]['file_exists'] = false;

                if (is_dir($foldername)) {
                    if (!is_writable($foldername)) {
                        $files[$i]['file_writable'] = false;

                        $simulateSuccess = false;
                        $this->log->warning(sprintf('[SIMULATE] The file "%s" could not be created!', $absoluteFilename));
                    } else {
                        $files[$i]['file_writable'] = true;
                    }
                } else {
                    $files[$i]['file_writable'] = true;

                    $this->log->debug(sprintf('[SIMULATE] The file "%s" could be created', $absoluteFilename));
                }
            }

            if ($filename == $this->updateScriptName) {
                $this->log->debug(sprintf('[SIMULATE] Update script "%s" found', $absoluteFilename));
                $files[$i]['update_script'] = true;
            } else {
                $files[$i]['update_script'] = false;
            }
        }

        $this->simulationResults = $files;

        return $simulateSuccess;
    }

    /**
     * Install update.
     *
     * @param string $updateFile Path to the update file
     * @param bool $simulateInstall Check for directory and file permissions instead of installing the update
     * @param $version
     * @return bool
     */
    protected function _install($updateFile, $simulateInstall, $version)
    {
        $this->log->notice(sprintf('Trying to install update "%s"', $updateFile));

        // Check if install should be simulated
        if ($simulateInstall) {
            if ($this->_simulateInstall($updateFile)) {
                $this->log->notice(sprintf('Simulation of update "%s" process succeeded', $version));

                return true;
            }

            $this->log->critical(sprintf('Simulation of update  "%s" process failed!', $version));

            return self::ERROR_SIMULATE;
        }

        clearstatcache();

        // Install only if simulateInstall === false

        // Check if zip file could be opened
        $zip = zip_open($updateFile);
        if (!is_resource($zip)) {
            $this->log->error(sprintf('Could not open zip file "%s", error: %d', $updateFile, $zip));

            return false;
        }

        // Read every file from archive
        while ($file = zip_read($zip)) {
            $filename = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, zip_entry_name($file));
            $foldername = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->installDir . dirname($filename));
            $absoluteFilename = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->installDir . $filename);
            $this->log->debug(sprintf('Updating file "%s"', $filename));

            if (!is_dir($foldername)) {
                if (!mkdir($foldername, $this->dirPermissions, true)) {
                    $this->log->error(sprintf('Directory "%s" has to be writeable!', $foldername));

                    return false;
                }
            }

            // Skip if entry is a directory
            if (substr($filename, -1, 1) == DIRECTORY_SEPARATOR) {
                continue;
            }

            // Read file contents from archive
            $contents = zip_entry_read($file, zip_entry_filesize($file));

            if ($contents === false) {
                $this->log->error(sprintf('Coud not read zip entry "%s"', $file));
                continue;
            }

            // Write to file
            if (file_exists($absoluteFilename)) {
                if (!is_writable($absoluteFilename)) {
                    $this->log->error(sprintf('Could not overwrite "%s"!', $absoluteFilename));

                    zip_close($zip);

                    return false;
                }
            } else {
                // touch will fail if PHP is not the owner of the file, and file_put_contents is faster than touch.
                if (file_put_contents($absoluteFilename, '') === false) {
                    $this->log->error(sprintf('The file "%s" could not be created!', $absoluteFilename));
                    zip_close($zip);

                    return false;
                }

                $this->log->debug(sprintf('File "%s" created', $absoluteFilename));
            }

            $updateHandle = fopen($absoluteFilename, 'w');

            if (!$updateHandle) {
                $this->log->error(sprintf('Could not open file "%s"!', $absoluteFilename));
                zip_close($zip);

                return false;
            }


            if (false === fwrite($updateHandle, $contents)) {
                $this->log->error(sprintf('Could not write to file "%s"!', $absoluteFilename));
                zip_close($zip);
                return false;
            }

            fclose($updateHandle);

            //If file is a update script, include
            if ($filename == $this->updateScriptName) {
                $this->log->debug(sprintf('Try to include update script "%s"', $absoluteFilename));
                require($absoluteFilename);

                $this->log->info(sprintf('Update script "%s" included!', $absoluteFilename));
                if (!unlink($absoluteFilename)) {
                    $this->log->warning(sprintf('Could not delete update script "%s"!', $absoluteFilename));
                }
            }
        }

        zip_close($zip);

        $this->log->notice(sprintf('Update "%s" successfully installed', $version));

        return true;
    }


    /**
     * Update to the latest version
     *
     * @param bool $simulateInstall Check for directory and file permissions before copying files (Default: true)
     * @param bool $deleteDownload Delete download after update (Default: true)
     * @return integer|bool
     * @throws DownloadException
     * @throws ParserException
     */
    public function update($simulateInstall = true, $deleteDownload = true)
    {
        $this->log->info('Trying to perform update');

        // Check for latest version
        if ($this->latestVersion === null || count($this->updates) === 0) {
            $this->checkUpdate();
        }

        if ($this->latestVersion === null || count($this->updates) === 0) {
            $this->log->error('Could not get latest version from server!');

            return self::ERROR_VERSION_CHECK;
        }

        // Check if current version is up to date
        if (!$this->newVersionAvailable()) {
            $this->log->warning('No update available!');

            return self::NO_UPDATE_AVAILABLE;
        }

        foreach ($this->updates as $update) {
            $this->log->debug(sprintf('Update to version "%s"', $update['version']));

            // Check for temp directory
            if (empty($this->tempDir) || !is_dir($this->tempDir) || !is_writable($this->tempDir)) {
                $this->log->critical(sprintf('Temporary directory "%s" does not exist or is not writeable!', $this->tempDir));

                return self::ERROR_TEMP_DIR;
            }

            // Check for install directory
            if (empty($this->installDir) || !is_dir($this->installDir) || !is_writable($this->installDir)) {
                $this->log->critical(sprintf('Install directory "%s" does not exist or is not writeable!', $this->installDir));

                return self::ERROR_INSTALL_DIR;
            }

            $updateFile = $this->tempDir . $update['version'] . '.zip';

            // Download update
            if (!is_file($updateFile)) {
                if (!$this->_downloadUpdate($update['url'], $updateFile)) {
                    $this->log->critical(sprintf('Failed to download update from "%s" to "%s"!', $update['url'], $updateFile));

                    return self::ERROR_DOWNLOAD_UPDATE;
                }

                $this->log->debug(sprintf('Latest update downloaded to "%s"', $updateFile));
            } else {
                $this->log->info(sprintf('Latest update already downloaded to "%s"', $updateFile));
            }

            // Install update
            $result = $this->_install($updateFile, $simulateInstall, $update['version']);
            if ($result === true) {
                $this->runOnEachUpdateFinishCallbacks($update['version']);
                if ($deleteDownload) {
                    $this->log->debug(sprintf('Trying to delete update file "%s" after successfull update', $updateFile));
                    if (unlink($updateFile)) {
                        $this->log->info(sprintf('Update file "%s" deleted after successfull update', $updateFile));
                    } else {
                        $this->log->error(sprintf('Could not delete update file "%s" after successfull update!', $updateFile));

                        return self::ERROR_DELETE_TEMP_UPDATE;
                    }
                }
            } else {
                if ($deleteDownload) {
                    $this->log->debug(sprintf('Trying to delete update file "%s" after failed update', $updateFile));
                    if (unlink($updateFile)) {
                        $this->log->info(sprintf('Update file "%s" deleted after failed update', $updateFile));
                    } else {
                        $this->log->error(sprintf('Could not delete update file "%s" after failed update!', $updateFile));
                    }
                }

                return $result;
            }
        }

        $this->runOnAllUpdateFinishCallbacks($this->getVersionsToUpdate());

        return true;
    }

    /**
     * Add slash at the end of the path.
     *
     * @param string $dir
     * @return string
     */
    public function addTrailingSlash($dir)
    {
        if (substr($dir, -1) != DIRECTORY_SEPARATOR) {
            $dir = $dir . DIRECTORY_SEPARATOR;
        }

        return $dir;
    }

    /**
     * Add callback which is executed after each update finished.
     *
     * @param callable $callback
     * @return $this
     */
    public function onEachUpdateFinish($callback)
    {
        $this->onEachUpdateFinishCallbacks[] = $callback;

        return $this;
    }

    /**
     * Add callback which is executed after all updates finished.
     *
     * @param callable $callback
     * @return $this
     */
    public function setOnAllUpdateFinishCallbacks($callback)
    {
        $this->onAllUpdateFinishCallbacks[] = $callback;

        return $this;
    }

    /**
     * Run callbacks after each update finished.
     *
     * @param string $updateVersion
     * @return void
     */
    private function runOnEachUpdateFinishCallbacks($updateVersion)
    {
        foreach ($this->onEachUpdateFinishCallbacks as $callback) {
            call_user_func($callback, $updateVersion);
        }
    }

    /**
     * Run callbacks after all updates finished.
     *
     * @param array $updatedVersions
     * @return void
     */
    private function runOnAllUpdateFinishCallbacks(array $updatedVersions)
    {
        foreach ($this->onAllUpdateFinishCallbacks as $callback) {
            call_user_func($callback, $updatedVersions);
        }
    }
}
