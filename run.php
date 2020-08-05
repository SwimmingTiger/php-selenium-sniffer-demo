<?php
// 老虎会游泳：我宣布放弃本文件的一切著作权，使其进入公有领域，请随意使用。

namespace Facebook\WebDriver;

use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\Chrome\ChromeDriverService;
use Facebook\WebDriver\Chrome\ChromeOptions;

require_once('vendor/autoload.php');

// 视频网址
$url = 'https://www.bilibili.com/video/BV1UK4y147XT';

// 要寻找的多媒体文件网址（正则表达式）
$findUrl = '/\.mp4\b/i';

// 播放按钮的css选择器
$playButtonSelector = '.player-icon';

// Chrome driver
// https://sites.google.com/a/chromium.org/chromedriver/downloads
$bin = '/usr/local/bin/chromedriver';
$port = 9515;

// 设为手机浏览器
$options = new ChromeOptions();
$mobileEmulation = ['deviceName' => 'iPhone X'];
$options->setExperimentalOption('mobileEmulation', $mobileEmulation);

// 开启性能记录
$caps = $options->toCapabilities();
$caps->setCapability('loggingPrefs', ['performance' => 'INFO']);

// 注意，不能使用 RemoteWebDriver 类，不然会报错。
// 只有 ChromeDriver 类才支持 performance 日志功能。
$service = new ChromeDriverService($bin, $port);
$driver = ChromeDriver::start($caps, $service);

$driver->get($url);

// 等待播放按钮出现
$driver->wait(10, 1000)->until(
    WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($playButtonSelector))
);

echo "网址：" . $driver->getCurrentURL() . "'\n";
echo "标题：" . $driver->getTitle() . "'\n";

// 点击播放按钮
$playButton = $driver->findElement(WebDriverBy::cssSelector($playButtonSelector));
$playButton->click();

// 等待加载（方法不好）。可能有更好的方法，参考：
// https://github.com/php-webdriver/php-webdriver/wiki/HowTo-Wait
sleep(5);

// 从网络日志中寻找视频文件
$logs = $driver->manage()->getLog('performance');

echo "视频文件（可能不完整，只是开头一部分）：\n";
foreach ($logs as $log) {
    $msg = json_decode($log['message']);
    if (empty($msg) || empty($msg->message) || empty($msg->message->params)
        || empty($msg->message->params->request) || empty($msg->message->params->request->url)) {
        continue;
    }
    $url = $msg->message->params->request->url;
    if (preg_match($findUrl, $url)) {
        echo "$url\n";
    }
}

// 关闭浏览器
$driver->quit();
