<?php

use atphp\youtube_downloader\YoutubeDownloader;

class YoutubeDownloaderTest extends PHPUnit_Framework_Testcase
{

    /**
     * @dataProvider dataDownload
     * @param $videoId
     * @param $format
     */
    public function testDownload($videoId, $format)
    {
        $yd = new YoutubeDownloader();
        $downloadUrl = $yd->getDownloadUrl($videoId, $format);
        $this->assertContains('videoplayback', $downloadUrl);
    }

    public function dataDownload()
    {
        return [
            ['9bZkp7q19f0', 'best'],
            ['kL2HJx3eZIM', 'free'],
            ['LXoWxrTdXkM', 'ipad'],
        ];
    }

}
