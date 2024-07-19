<?php

function getVideoTitleUrl(string $url = "https://streamable.com/moo", string $referrer = 'https://reddit.com/'): array
{
    $crl = curl_init();
    curl_setopt($crl, CURLOPT_URL, $url);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($crl, CURLOPT_HEADER, true);
    $headers = [
        'Host: streamable.com',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*',
        'Accept-Encoding: gzip, deflate, br',
        'Accept-Language: en-US,en;q=0.5',
        'Cache-Control: no-cache',
        'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        'Referer: ' . $referrer,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:93.0) Gecko/20100101 Firefox/93.0'
    ];
    curl_setopt($crl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($crl, CURLOPT_ENCODING, "");
    $response = curl_exec($crl);

    if (curl_errno($crl)) {
        curl_close($crl);
        return ['success' => false, 'message' => curl_error($crl), 'url' => $url];
    }

    $http_code = curl_getinfo($crl, CURLINFO_HTTP_CODE);

    $body = substr($response, curl_getinfo($crl, CURLINFO_HEADER_SIZE));

    curl_close($crl);

    if ($http_code === 200) {

        $doc = new DOMDocument();
        @$doc->loadHTML($body);

        $meta_tags = $doc->getElementsByTagName('meta');
        foreach ($meta_tags as $meta) {

            if ($meta->getAttribute('property') === 'og:video:url') {
                $video_url = $meta->getAttribute('content');
                break;
            }

        }

        if (!isset($video_url) && isset($doc->getElementsByTagName('video')[0])) {
            $video_url = str_replace("//", "https://", $doc->getElementsByTagName('video')[0]->getAttribute('src'));
        }

        $xpath = new DOMXPath($doc);
        $headers = $xpath->query("//h1[contains(concat(' ', normalize-space(@class), ' '), 'text-xl text-label-primary font-semibold m-0 break-all')]");
        if ($headers->length > 0) {
            $video_title = $headers->item(0)->textContent;
        }

        if (!isset($video_url)) {
            return ['success' => false, 'message' => 'video url was not found', 'url' => $url, 'http_code' => $http_code];
        }

        return ['success' => true, 'title' => $video_title ?? null, 'video_url' => $video_url, 'url' => $url, 'http_code' => $http_code];
    }

    return ['success' => false, 'message' => 'HTTP ' . $http_code, 'url' => $url, 'http_code' => $http_code];
}


$result = getVideoTitleUrl("https://streamable.com/moo");

echo json_encode($result);
