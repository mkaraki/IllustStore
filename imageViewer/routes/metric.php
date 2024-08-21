<?php
global $klein;
$klein->respond('GET', '/metrics', function ($request, $response) {
    $ret = "# HELP illust_count Number of illusts registerd in DB.\n";
    $ret .= "# TYPE illust_count gauge\n";
    $imgCount = DB::queryFirstField('SELECT count(i.id) FROM illusts i');
    $ret .= 'illust_count ' . $imgCount . "\n";

    $ret .= "# HELP jpeg_illust_count Number of jpeg illusts registerd in DB.\n";
    $ret .= "# TYPE jpeg_illust_count gauge\n";
    $imgCount = DB::queryFirstField("SELECT COUNT(*) FROM illusts WHERE path LIKE '%.jpg' OR path LIKE '%.jpeg'");
    $ret .= 'jpeg_illust_count ' . $imgCount . "\n";

    $ret .= "# HELP png_illust_count Number of png illusts registerd in DB.\n";
    $ret .= "# TYPE png_illust_count gauge\n";
    $imgCount = DB::queryFirstField("SELECT COUNT(*) FROM illusts WHERE path LIKE '%.png'");
    $ret .= 'png_illust_count ' . $imgCount . "\n";

    $ret .= "# HELP webp_illust_count Number of webp illusts registerd in DB.\n";
    $ret .= "# TYPE webp_illust_count gauge\n";
    $imgCount = DB::queryFirstField("SELECT COUNT(*) FROM illusts WHERE path LIKE '%.webp'");
    $ret .= 'webp_illust_count ' . $imgCount . "\n";

    $ret .= "# HELP lepton_illust_count Number of lepton illusts registerd in DB.\n";
    $ret .= "# TYPE lepton_illust_count gauge\n";
    $imgCount = DB::queryFirstField("SELECT COUNT(*) FROM illusts WHERE path LIKE '%.lep'");
    $ret .= 'lepton_illust_count ' . $imgCount . "\n";

    $ret .= "# HELP tag_count Number of tags registerd in DB.\n";
    $ret .= "# TYPE tag_count gauge\n";
    $tagCount = DB::queryFirstField('SELECT count(t.id) FROM tags t');
    $ret .= 'tag_count ' . $tagCount . "\n";

    $ret .= "# HELP tag_assign_count Number of tags assign information table in DB.\n";
    $ret .= "# TYPE tag_assign_count gauge\n";
    $tagCount = DB::queryFirstField('SELECT count(tA.illustId) FROM tagAssign tA');
    $ret .= 'tag_assign_count ' . $tagCount . "\n";

    $opcache_status = opcache_get_status(false);
    if ($opcache_status !== false) {
        $opcache_stats = $opcache_status["opcache_statistics"];

        $ret .= "# HELP opcache_cached_script_count Number of cached scripts\n";
        $ret .= "# TYPE opcache_cached_script_count gauge\n";
        $ret .= 'opcache_cached_script_count ' . $opcache_stats["num_cached_scripts"] . "\n";

        $ret .= "# HELP opcache_cached_key_count Number of cached scripts\n";
        $ret .= "# TYPE opcache_cached_key_count gauge\n";
        $ret .= 'opcache_cached_key_count ' . $opcache_stats["num_cached_keys"] . "\n";

        $ret .= "# HELP opcache_hit_rate Opcache hit rate\n";
        $ret .= "# TYPE opcache_hit_rate gauge\n";
        $ret .= 'opcache_hit_rate ' . $opcache_stats["opcache_hit_rate"] . "\n";
    }

    $response->header('Content-Type', 'text/plain; charset=utf-8');
    $response->body($ret);
});
