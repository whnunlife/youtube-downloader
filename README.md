Youtube downloader library [![Build Status](https://travis-ci.org/atphp/youtube-downloader.svg?branch=v0.1)](https://travis-ci.org/atphp/youtube-downloader)
=======

Download it using composer

```bash
composer require atphp/youtube-downloader
```

Use it

```php
use atphp\youtube_downloader\YoutubeDownloader;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;

require_once __DIR__ . '/vendor/autoload.php';

$logger = new Logger('at.youtube-downloader');
$logger->pushHandler(new ErrorLogHandler());

$yd = new YoutubeDownloader();
$yd->setLogger($logger);

print_r([
    'downloadURL' => $yd->getDownloadUrl($videoId = 'U839NZ78EOo', $format = 'best')
]);
```
