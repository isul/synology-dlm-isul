#!/bin/sh

TORRENT_NAME=torrentsoda
rm -rf $TORRENT_NAME.dlm
tar zcf $TORRENT_NAME.dlm INFO search.php simple_html_dom.php
echo Torrent Search Module built successfully: $TORRENT_NAME.dlm