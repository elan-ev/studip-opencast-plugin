<?php

$target = 'zip';

if (isset($_SERVER['argv'][1])) {
    $target = $_SERVER['argv'][1];
}

switch ($target) {
    case 'zip':
        zip();
        break;
    case 'translate':
        translate();
        break;
    default:
        zip();
        break;
}


function translate()
{
    shell_exec('find * \( -iname "*.php" -o -iname "*.ihtml" \) | xargs xgettext --from-code=UTF-8 -j --add-location=never --package-name=Opencast --language=PHP -o "locale/en/LC_MESSAGES/opencast.po"');
    #shell_exec('msgconv --to-code=CP1252 "locale/en/LC_MESSAGES/opencast.po" -o "locale/en/LC_MESSAGES/opencast.po"');
    shell_exec('msgfmt "locale/en/LC_MESSAGES/opencast.po" --output-file="locale/en/LC_MESSAGES/opencast.mo"');
}

/**
 * Creates the Stud.IP plugin zip archive.
 */
function zip()
{
    $archive = new ZipArchive();
    $archive->open('opencast.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
    addDirectories($archive, array(
        'classes',
        'controllers',
        'cronjobs',
        'images',
        'javascripts',
        'locale',
        'migrations',
        'models',
        'sql',
        'stylesheets',
        'vendor',
        'views',
    ), '/^(assets|blocks).*\.less$/');
    $archive->addFile('README');
    $archive->addFile('OpenCast.class.php');
    $archive->addFile('plugin.manifest');
    $archive->close();

    printSuccess('created the Stud.IP plugin zip archive');
}

/**
 * Recursively adds a directory tree to a zip archive.
 *
 * @param ZipArchive $archive           The zip archive
 * @param string     $directory         The directory to add
 * @param string     $ignoredFilesRegex Regular expression that matches
 *                                      files which should be ignored
 */
function addDirectory(ZipArchive $archive, $directory, $ignoredFilesRegex = '')
{
    $archive->addEmptyDir($directory);

    foreach (glob($directory.'/*') as $file) {
        if (is_dir($file)) {
            addDirectory($archive, $file, $ignoredFilesRegex);
        } else {
            if ($ignoredFilesRegex === '' || !preg_match($ignoredFilesRegex, $file)) {
                $archive->addFile($file);
            } else {
                printError('ignore '.$file);
            }
        }
    }
}

/**
 * Recursively adds directory trees to a zip archive.
 *
 * @param ZipArchive $archive           The zip archive
 * @param array      $directories       The directories to add
 * @param string     $ignoredFilesRegex Regular expression that matches
 *                                      files which should be ignored
 */
function addDirectories(ZipArchive $archive, array $directories, $ignoredFilesRegex = '')
{
    foreach ($directories as $directory) {
        addDirectory($archive, $directory, $ignoredFilesRegex);
    }
}

/**
 * Prints a success message to the standard output stream of the console.
 *
 * @param string $message The message to print
 */
function printSuccess($message)
{
    echo "\033[32m".$message."\033[39m".PHP_EOL;
}

/**
 * Prints an info message to the standard output stream of the console.
 *
 * @param string $message The message to print
 */
function printInfo($message)
{
    echo $message.PHP_EOL;
}

/**
 * Prints an error message to the standard output stream of the console.
 *
 * @param string $message The message to print
 */
function printError($message)
{
    file_put_contents('php://stderr', "\033[31m".$message."\033[39m".PHP_EOL);
}
