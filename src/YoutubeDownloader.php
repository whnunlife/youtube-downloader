<?php

namespace atphp\youtube_downloader;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class YoutubeDownloader
{

    use LoggerAwareTrait;

    /** @var  ClientInterface */
    protected $client;

    /** @var  array */
    protected $info;

    protected $formats = [
        'best'    => ['38', '37', '46', '22', '45', '35', '44', '34', '18', '43', '6', '5', '17', '13'],
        // WebM but prefer it over FLV
        'free'    => ['38', '46', '37', '45', '22', '44', '35', '43', '34', '18', '6', '5', '17', '13'],
        // leave out WebM video and FLV - looking for MP4
        'ipad'    => ['37', '22', '18', '17'],
        'default' => ['38', '37', '46', '22', '45', '35', '44', '34', '18', '43', '6', '5', '17', '13'],
    ];

    public function getLogger()
    {
        if (null === $this->logger) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }

    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        if (null === $this->client) {
            $this->client = new Client();
        }
        return $this->client;
    }

    /**
     * Get video info from Youtube, parse it the readable format.
     *
     * @param string $videoId
     * @return array
     */
    protected function doGetInfo($videoId)
    {
        $this->info = [];

        $url = 'http://www.youtube.com/get_video_info?&video_id=' . $videoId . '&asv=3&el=detailpage&hl=en_US';
        $this->getLogger()->debug("GET URL: {$url}");
        $info = (string) $this->getClient()->get($url)->getBody();
        parse_str($info, $this->info);

        if (!isset($this->info['url_encoded_fmt_stream_map'])) {
            throw new \RuntimeException('No encoded format stream found.');
        }

        foreach (['adaptive_fmts', 'caption_translation_languages'] as $i) {
            isset($this->info[$i]) && parse_str($this->info[$i], $this->info[$i]);
        }

        foreach (['keywords', 'url_encoded_fmt_stream_map', 'rvs'] as $i) {
            if (isset($this->info[$i])) {
                $this->info[$i] = explode(',', $this->info[$i]);
            }
        }

        foreach (['url_encoded_fmt_stream_map', 'rvs'] as $i) {
            if (isset($this->info[$i])) {
                foreach ($this->info[$i] as $k => $v) {
                    parse_str($v, $this->info[$i][$k]);
                }
            }
        }

        foreach ($this->info['url_encoded_fmt_stream_map'] as &$format) {
            $format['type'] = explode(';', $format['type'])[0];
            parse_str($format['url'], $format['info']);
        }

        return $this->info;
    }

    /**
     * Get video info with cache.
     *
     * @param $videoId
     * @return array
     */
    protected function getInfo($videoId)
    {
        if (null === $this->info) {
            $this->info = $this->doGetInfo($videoId);
        }
        return $this->info;
    }

    protected function findBestFormat($videoId, $format)
    {
        if (!isset($this->formats[$format])) {
            throw new \RuntimeException('Invalid format: ' . $format);
        }

        $items = $this->getInfo($videoId)['url_encoded_fmt_stream_map'];
        foreach ($items as $item) {
            foreach ($this->formats[$format] as $iTag) {
                if ($iTag == $item['itag']) {
                    $this->getLogger()->debug('Video format: ' . print_r($item, true));
                    return $item;
                }
            }
        }

        throw new \RuntimeException('No format found.');
    }

    /**
     * @param string $videoId
     * @param string $format Available options: best, free, ipad, default
     * @return null|string
     */
    public function getDownloadUrl($videoId, $format = 'best')
    {
        return $this->findBestFormat($videoId, $format)['url'];
    }

    public function download($localPath, $videoId, $format = 'best')
    {
        $localDir = dirname($localPath);
        if (!is_writable($localDir)) {
            throw new \RuntimeException('Not writable directory: ' . $localDir);
        }

        $url = $this->getDownloadUrl($videoId, $format);
        $this->getClient()->get($url, ['save_to' => $localPath]);
    }

}
