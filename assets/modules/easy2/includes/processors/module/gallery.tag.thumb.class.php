<?php

class ModuleTagThumbProcessor extends E2GProcessor {

    public function process() {
        $output = '';

        $index = $this->modx->e2gMod->e2gModCfg['index'];
        $index = str_replace('assets/modules/easy2/includes/connector/', '', $index);

        $rootDir = '../../../../../' . $this->modx->e2gMod->e2g['dir'];
        $tag = $this->config['tag'];
        $gdir = $this->modx->e2gMod->e2g['dir'] . $this->config['path'];

        //******************************************************************/
        //**************************** Dir tags ****************************/
        //******************************************************************/
        $selectDirs = 'SELECT * FROM ' . $this->modx->db->config['table_prefix'] . 'easy2_dirs '
                . 'WHERE cat_tag LIKE \'%' . $tag . '%\' '
                . 'ORDER BY cat_name ASC';
        $querySelectDirs = mysql_query($selectDirs);
        if (!$querySelectDirs) {
            $err = __LINE__ . ' : #' . mysql_errno() . ' ' . mysql_error() . '<br />' . $selectDirs;
            $this->modx->e2gMod->setError($err);
            return FALSE;
        }

        $fetchDirs = array();
        while ($l = mysql_fetch_assoc($querySelectDirs)) {
            $fetchDirs[$l['cat_name']] = $l;
        }
        mysql_free_result($querySelectDirs);
        uksort($fetchDirs, "strnatcmp");

        //******************************************************************/
        //*************************** FILE tags ****************************/
        //******************************************************************/
        $selectFiles = 'SELECT * FROM ' . $this->modx->db->config['table_prefix'] . 'easy2_files '
                . 'WHERE tag LIKE \'%' . $tag . '%\' '
                . 'ORDER BY id ASC';
        $querySelectFiles = mysql_query($selectFiles);
        if (!$querySelectFiles) {
            $err = __LINE__ . ' : #' . mysql_errno() . ' ' . mysql_error() . '<br />' . $selectFiles;
            $this->modx->e2gMod->setError($err);
            return FALSE;
        }

        $fetchFiles = array();
        while ($l = mysql_fetch_assoc($querySelectFiles)) {
            $fetchFiles[$l['filename']] = $l;
        }
        mysql_free_result($querySelectFiles);
        uksort($fetchFiles, "strnatcmp");

        $rowClass = array(' class="gridAltItem"', ' class="gridItem"');
        $rowNum = 0;

        header('Content-Type: text/html; charset=\'' . $this->modx->e2gMod->lng['charset'] . '\'');

        #########################     DIRECTORIES      #########################
        $dirPhRow = array();

        foreach ($fetchDirs as $fetchDir) {
            // goldsky -- store the array to be connected between db <--> fs
            $dirPhRow['thumb.parent_id'] = $fetchDir['parent_id'];
            $dirPhRow['thumb.id'] = $fetchDir['cat_id'];
            $dirPhRow['thumb.name'] = $fetchDir['cat_name'];
            $dirPhRow['thumb.alias'] = $fetchDir['cat_alias'];
            $dirPhRow['thumb.tag'] = $fetchDir['cat_tag'];
            $dirPhRow['thumb.cat_visible'] = $fetchDir['cat_visible'];
            $dirPhRow['thumb.date_added'] = $fetchDir['date_added'];
            $dirPhRow['thumb.last_modified'] = $fetchDir['last_modified'];

            ####################### Template placeholders ######################

            $dirPhRow['thumb.rowNum'] = $rowNum;
            $dirPhRow['thumb.rowClass'] = $rowClass[$rowNum % 2];
            $dirPath = $gdir . $this->modx->e2gMod->getPath($fetchDir['parent_id']);
            $dirPhRow['thumb.checkBox'] = '
                <input name="dir[' . $fetchDir['cat_id'] . ']" value="' . rawurldecode($dirPath . $fetchDir['cat_name']) . '" type="checkbox" style="border:0;padding:0" />
                ';
            $dirPhRow['thumb.gid'] = '[id: ' . $fetchDir['cat_id'] . ']';
            $dirPhRow['thumb.path'] = $dirPath;
            $dirPhRow['thumb.pathRawUrlEncoded'] = str_replace('%2F', '/', rawurlencode($dirPath . $fetchDir['cat_name']));
            $dirPhRow['thumb.title'] = ( trim($fetchDir['cat_alias']) != '' ? $fetchDir['cat_alias'] : $fetchDir['cat_name']);

            $dirStyledName = $fetchDir['cat_name']; // will be overridden for styling below
            $dirCheckBox = '';
            $dirLink = '';
            $dirAttributes = '';
            $dirAttributeIcons = '';
            $dirIcon = '
                <i class="icon-custom icon-no-border icon-color-yellow fa fa-folder icon-vs"></i>
                ';
            if (!empty($fetchDir['cat_redirect_link'])) {
                $dirIcon .= '
                <i class="icon-custom icon-no-border icon-bg-purple fa fa-link fa-2x icon-vs" title="' . $this->modx->e2gMod->lng['redirect_link'] . ': ' . $fetchDir['cat_redirect_link'] . '"></i>
                        ';
            }
            $dirButtons = '';

            if ($fetchDir['cat_visible'] == '1') {
                $dirStyledName = '<b>' . $fetchDir['cat_name'] . '</b>';
                $dirButtons = $this->modx->e2gMod->actionIcon('hide_dir', array(
                    'act' => 'hide_dir'
                    , 'dir_id' => $fetchDir['cat_id']
                    , 'pid' => $fetchDir['parent_id']
                        ), null, $index);
            } else {
                $dirAttributes = '<i>(' . $this->modx->e2gMod->lng['hidden'] . ')</i>';
                $dirAttributeIcons = '
                <a href="' . $index . '&amp;act=unhide_dir&amp;dir_id=' . $fetchDir['cat_id'] . '&amp;pid=' . $fetchDir['parent_id'] . '">
                    <i class="icon-custom icon-no-border icon-bg-aqua fa fa-eye-slash fa-2x icon-vs" title="' . $this->modx->e2gMod->lng['hidden'] . '"></i>
                </a>
                ';
                $dirButtons = $this->modx->e2gMod->actionIcon('unhide_dir', array(
                    'act' => 'unhide_dir'
                    , 'dir_id' => $fetchDir['cat_id']
                    , 'pid' => $fetchDir['parent_id']
                        ), null, $index);
            }

            $dirButtons .= $this->modx->e2gMod->actionIcon('edit_dir', array(
                'page' => 'edit_dir'
                , 'dir_id' => $fetchDir['cat_id']
                , 'tag' => $tag
                    ), null, $index);
            $dirButtons .= $this->modx->e2gMod->actionIcon('delete_dir', array(
                'act' => 'delete_dir'
                , 'dir_path' => $dirPath . $fetchDir['cat_name']
                , 'dir_id' => $fetchDir['cat_id']
                , 'tag' => $tag
                    ), 'onclick="return confirmDeleteFolder();"', $index);

            $dirPhRow['thumb.styledName'] = $dirStyledName;
            $dirPhRow['thumb.attributes'] = $dirAttributes;
            $dirPhRow['thumb.attributeIcons'] = $dirAttributeIcons;
            $dirPhRow['thumb.href'] = $index . '&amp;pid=' . $fetchDir['cat_id'];
            $dirPhRow['thumb.buttons'] = $dirButtons;
            $dirPhRow['thumb.icon'] = $dirIcon;
            $dirPhRow['thumb.size'] = '---';
            $dirPhRow['thumb.w'] = '---';
            $dirPhRow['thumb.h'] = '---';
            $dirPhRow['thumb.mod_w'] = $this->modx->e2gMod->e2g['mod_w'];
            $dirPhRow['thumb.mod_h'] = $this->modx->e2gMod->e2g['mod_h'];
            $dirPhRow['thumb.mod_thq'] = $this->modx->e2gMod->e2g['mod_thq'];

            ############################################################################

            $dirPhRow['thumb.src'] = '';
            $dirPhRow['thumb.thumb'] = '';
            if (!empty($dirPhRow['thumb.id'])) {
                // search image for subdir
                $folderImgInfos = $this->modx->e2gMod->folderImg($dirPhRow['thumb.id'], $rootDir);

                // if there is an empty folder, or invalid content
                if ($folderImgInfos === FALSE) {
                    $imgPreview = E2G_MODULE_URL . 'preview.easy2gallery.php?path='
                            . $dirPhRow['thumb.pathRawUrlEncoded']
                            . '&amp;mod_w=' . $dirPhRow['thumb.mod_w']
                            . '&amp;mod_h=' . $dirPhRow['thumb.mod_h']
                            . '&amp;text=' . $this->modx->e2gMod->lng['empty']
                    ;
                    $dirPhRow['thumb.thumb'] = '
            <a href="' . $dirPhRow['thumb.href'] . '">
                <img src="' . $imgPreview
                            . '" alt="' . $dirPhRow['thumb.path'] . $dirPhRow['thumb.name']
                            . '" title="' . $dirPhRow['thumb.title']
                            . '" width="' . $dirPhRow['thumb.mod_w']
                            . '" height="' . $dirPhRow['thumb.mod_h']
                            . '" />
            </a>
';
                } else {
                    // path to subdir's thumbnail
                    $pathToImg = $this->modx->e2gMod->getPath($folderImgInfos['dir_id']);
                    $imgShaper = $this->modx->e2gMod->imgShaper($rootDir
                            , $pathToImg . $folderImgInfos['filename']
                            , $dirPhRow['thumb.mod_w']
                            , $dirPhRow['thumb.mod_w']
                            , $dirPhRow['thumb.mod_thq']);
                    if ($imgShaper === FALSE) {
                        // folder has been deleted
                        $imgPreview = E2G_MODULE_URL . 'preview.easy2gallery.php?path='
                                . $dirPhRow['thumb.pathRawUrlEncoded']
                                . '&amp;mod_w=' . $dirPhRow['thumb.mod_w']
                                . '&amp;mod_h=' . $dirPhRow['thumb.mod_h']
                                . '&amp;text=' . $this->modx->e2gMod->lng['deleted']
                        ;
                        $imgSrc = E2G_MODULE_URL . 'preview.easy2gallery.php?path='
                                . $dirPhRow['thumb.pathRawUrlEncoded']
                                . '&amp;mod_w=300'
                                . '&amp;mod_h=100'
                                . '&amp;text=' . $this->modx->e2gMod->lng['deleted']
                                . '&amp;th=5';
                        $dirPhRow['thumb.thumb'] = '
            <a href="' . $imgSrc
                                . '" class="highslide" onclick="return hs.expand(this)"'
                                . ' title="' . $dirPhRow['thumb.name'] . ' ' . $dirPhRow['thumb.gid'] . ' ' . $dirPhRow['thumb.attributes']
                                . '">
                <img src="' . $imgPreview
                                . '" alt="' . $dirPhRow['thumb.path'] . $dirPhRow['thumb.name']
                                . '" title="' . $dirPhRow['thumb.title']
                                . '" width="' . $dirPhRow['thumb.mod_w']
                                . '" height="' . $dirPhRow['thumb.mod_h']
                                . '" />
            </a>
';
                        unset($imgPreview);
                    } else {
                        /**
                         * $imgShaper returns the URL to the image
                         */
                        $dirPhRow['thumb.src'] = $imgShaper;

                        /**
                         * @todo: AJAX call to the image
                         */
                        $dirPhRow['thumb.thumb'] = '
            <a href="' . $dirPhRow['thumb.href'] . '">
                <img src="' . '../' . str_replace('../', '', $imgShaper)
                                . '" alt="' . $dirPhRow['thumb.name']
                                . '" title="' . $dirPhRow['thumb.title']
                                . '" width="' . $dirPhRow['thumb.mod_w']
                                . '" height="' . $dirPhRow['thumb.mod_h']
                                . '" class="thumb-dir" />
                <span class="preloader" id="thumbDir_' . $dirPhRow['thumb.rowNum'] . '">
                    <script type="text/javascript">
                        thumbDir(\'' . '../' . str_replace('../', '', $imgShaper) . '\','
                                . $dirPhRow['thumb.rowNum'] . ');
                    </script>
                </span>
            </a>
';
                        unset($imgShaper);
                    }
                }
            } else {
                $imgPreview = E2G_MODULE_URL . 'preview.easy2gallery.php?path='
                        . $dirPhRow['thumb.pathRawUrlEncoded']
                        . '&amp;mod_w=' . $dirPhRow['thumb.mod_w']
                        . '&amp;mod_h=' . $dirPhRow['thumb.mod_h']
                        . '&amp;text=' . $this->modx->e2gMod->lng['new'];
                $dirPhRow['thumb.thumb'] = '
            <a href="' . $dirPhRow['thumb.href'] . '">
                <img src="' . $imgPreview
                        . '" alt="' . $dirPhRow['thumb.name']
                        . '" title="' . $dirPhRow['thumb.title']
                        . '" width="' . $dirPhRow['thumb.mod_w']
                        . '" height="' . $dirPhRow['thumb.mod_h']
                        . '" />
            </a>
';
                unset($imgPreview);
            }

            $output .= $this->modx->e2gMod->filler($this->modx->e2gMod->getTpl('file_thumb_dir_tpl'), $dirPhRow);

            $rowNum++;
        }

        #########################     FILES      #########################
        $filePhRow = array();

        foreach ($fetchFiles as $fetchFile) {
            $filePhRow['thumb.id'] = $fetchFile['id'];
            $filePhRow['thumb.dirId'] = $fetchFile['dir_id'];
            $filePhRow['thumb.name'] = $fetchFile['filename'];
            $filePhRow['thumb.size'] = round($fetchFile['size'] / 1024);
            $filePhRow['thumb.w'] = $fetchFile['width'];
            $filePhRow['thumb.h'] = $fetchFile['height'];
            $filePhRow['thumb.alias'] = $fetchFile['alias'];
            $filePhRow['thumb.tag'] = $fetchFile['tag'];
            $filePhRow['thumb.date_added'] = $fetchFile['date_added'];
            $filePhRow['thumb.last_modified'] = $fetchFile['last_modified'];
            $filePhRow['thumb.status'] = $fetchFile['status'];

            ####################### Template placeholders ######################

            $fileStyledName = $fetchFile['filename']; // will be overridden for styling below
            $fileAlias = '';
            $fileTag = '';
            $fileTagLinks = '';
            $fileAttributes = '';
            $fileAttributeIcons = '';
            $fileIcon = '
            <i class="icon-custom icon-no-border icon-bg-blue fa fa-picture-o fa-2x icon-vs"></i>
            ';
            if (!empty($fetchFile['redirect_link'])) {
                $fileIcon .= '
                <i class="icon-custom icon-no-border icon-bg-purple fa fa-link fa-2x icon-vs" title="' . $this->modx->e2gMod->lng['redirect_link'] . ': ' . $fetchFile['redirect_link'] . '"></i>
                        ';
            }
            $fileButtons = '';

            $filePhRow['thumb.rowNum'] = $rowNum;
            $filePhRow['thumb.rowClass'] = $rowClass[$rowNum % 2];
            $filePath = $gdir . $this->modx->e2gMod->getPath($fetchFile['dir_id']);
            $fileNameUrlDecodeFilename = urldecode($fetchFile['filename']);
            $filePathRawUrlEncoded = str_replace('%2F', '/', rawurlencode($filePath . $fetchFile['filename']));

            if (!file_exists(realpath('../../../../../' . $filePath . $fetchFile['filename']))) {
                $fileIcon = '
					<i class="icon-custom icon-no-border icon-bg-red fa fa-minus-circle fa-2x icon-vs"></i>
                ';
                $fileStyledName = '<b style="color:red;"><u>' . $fetchFile['filename'] . '</u></b>';
                $fileAttributes = '<i>(' . $this->modx->e2gMod->lng['deleted'] . ')</i>';
            } else {
                if ($fetchFile['status'] == '1') {
                    $fileButtons = $this->modx->e2gMod->actionIcon('hide_file', array(
                        'act' => 'hide_file'
                        , 'file_id' => $fetchFile['id']
                        , 'tag' => $tag
                            ), null, $index);
                } else {
                    $fileStyledName = '<i>' . $fetchFile['filename'] . '</i>';
                    $fileAttributes = '<i>(' . $this->modx->e2gMod->lng['hidden'] . ')</i>';
                    $fileAttributeIcons = $this->modx->e2gMod->actionIcon('unhide_file', array(
                        'act' => 'unhide_file'
                        , 'file_id' => $fetchFile['id']
                        , 'tag' => $tag
                            ), null, $index);
                    $fileButtons = $this->modx->e2gMod->actionIcon('unhide_file', array(
                        'act' => 'unhide_file'
                        , 'file_id' => $fetchFile['id']
                        , 'tag' => $tag
                            ), null, $index);
                }
            }

            $fileButtons .= $this->modx->e2gMod->actionIcon('comments', array(
                'page' => 'comments'
                , 'file_id' => $fetchFile['id']
                , 'tag' => $tag
                    ), null, $index);

            $fileButtons .= $this->modx->e2gMod->actionIcon('edit_file', array(
                'page' => 'edit_file'
                , 'file_id' => $fetchFile['id']
                , 'tag' => $tag
                    ), null, $index);

            $fileButtons .= $this->modx->e2gMod->actionIcon('delete_file', array(
                'act' => 'delete_file'
                , 'pid' => $fetchFile['dir_id']
                , 'file_path' => $filePathRawUrlEncoded
                , 'file_id' => $fetchFile['id']
                    ), 'onclick="return confirmDelete();"', $index);

            $filePhRow['thumb.checkBox'] = '
                <input name="im[' . $fetchFile['id'] . ']" value="im[' . $fetchFile['id'] . ']" type="checkbox" style="border:0;padding:0" />
                ';
            $filePhRow['thumb.dirId'] = $fetchFile['dir_id'];
            $filePhRow['thumb.fid'] = '[id:' . $fetchFile['id'] . ']';
            $filePhRow['thumb.styledName'] = $fileStyledName;
            $filePhRow['thumb.title'] = ( trim($fetchFile['alias']) != '' ? $fetchFile['alias'] : $fetchFile['filename']);
            $filePhRow['thumb.path'] = $filePath;
            $filePhRow['thumb.pathRawUrlEncoded'] = $filePathRawUrlEncoded;
            $filePhRow['thumb.attributes'] = $fileAttributes;
            $filePhRow['thumb.attributeIcons'] = $fileAttributeIcons;
            $filePhRow['thumb.buttons'] = $fileButtons;
            $filePhRow['thumb.icon'] = $fileIcon;
            $filePhRow['thumb.mod_w'] = $this->modx->e2gMod->e2g['mod_w'];
            $filePhRow['thumb.mod_h'] = $this->modx->e2gMod->e2g['mod_h'];
            $filePhRow['thumb.mod_thq'] = $this->modx->e2gMod->e2g['mod_thq'];

            ############################################################################

            $filePhRow['thumb.link'] = '';
            $filePhRow['thumb.src'] = '';
            $filePhRow['thumb.thumb'] = '';
            if (!empty($filePhRow['thumb.id'])) {
                // path to subdir's thumbnail
                $pathToImg = $this->modx->e2gMod->getPath($filePhRow['thumb.dirId']);
                $imgShaper = $this->modx->e2gMod->imgShaper($rootDir
                        , $pathToImg . $filePhRow['thumb.name']
                        , $filePhRow['thumb.mod_w']
                        , $filePhRow['thumb.mod_w']
                        , $filePhRow['thumb.mod_thq']
                );

                // if there is an invalid content
                if ($imgShaper === FALSE) {
                    $imgPreview = E2G_MODULE_URL . 'preview.easy2gallery.php?path='
                            . '&amp;mod_w=' . $filePhRow['thumb.mod_w']
                            . '&amp;mod_h=' . $filePhRow['thumb.mod_h']
                            . '&amp;text=' . __LINE__ . '-FALSE'
                    ;
                    $filePhRow['thumb.thumb'] = '
                <a href="' . $imgPreview
                            . '" class="highslide" onclick="return hs.expand(this)"'
                            . ' title="' . $filePhRow['thumb.name'] . ' ' . $filePhRow['thumb.fid'] . ' ' . $filePhRow['thumb.attributes']
                            . '">
                    <img src="' . $imgPreview
                            . '" alt="' . $filePhRow['thumb.path'] . $filePhRow['thumb.name']
                            . '" title="' . $filePhRow['thumb.title']
                            . '" width="' . $filePhRow['thumb.mod_w']
                            . '" height="' . $filePhRow['thumb.mod_h']
                            . '" />
                </a>
    ';
                } else {
                    $filePhRow['thumb.src'] = $imgShaper;
                    $filePhRow['thumb.thumb'] = '
            <a href="../' . $filePhRow['thumb.pathRawUrlEncoded']
                            . '" class="highslide" onclick="return hs.expand(this, { objectType: \'ajax\'})" '
                            . 'title="' . $filePhRow['thumb.name'] . ' ' . $filePhRow['thumb.fid'] . ' ' . $filePhRow['thumb.attributes']
                            . '">
                <img src="' . '../' . str_replace('../', '', $imgShaper)
                            . '" alt="' . $filePhRow['thumb.pathRawUrlEncoded'] . $filePhRow['thumb.name']
                            . '" title="' . $filePhRow['thumb.title']
                            . '" width="' . $filePhRow['thumb.mod_w']
                            . '" height="' . $filePhRow['thumb.mod_h']
                            . '" class="thumb-file" />
            </a>
';
                }
                unset($imgShaper);
            } else {
                // new image
                $imgPreview = E2G_MODULE_URL . 'preview.easy2gallery.php?path='
                        . $filePhRow['thumb.pathRawUrlEncoded']
                        . '&amp;mod_w=' . $filePhRow['thumb.mod_w']
                        . '&amp;mod_h=' . $filePhRow['thumb.mod_h']
                        . '&amp;text=' . __LINE__ . '-' . $this->modx->e2gMod->lng['new']
                ;
                $filePhRow['thumb.thumb'] = '
            <a href="' . $filePhRow['thumb.path'] . $filePhRow['thumb.name']
                        . '" class="highslide" onclick="return hs.expand(this)"'
                        . ' title="' . $filePhRow['thumb.name'] . ' ' . $filePhRow['thumb.fid'] . ' ' . $filePhRow['thumb.attributes']
                        . '">
                <img src="' . $imgPreview
                        . '" alt="' . $filePhRow['thumb.path'] . $filePhRow['thumb.name']
                        . '" title="' . $filePhRow['thumb.title']
                        . '" width="' . $filePhRow['thumb.mod_w']
                        . '" height="' . $filePhRow['thumb.mod_h']
                        . '" />
            </a>
';
                unset($imgPreview);
            }

            $output .= $this->modx->e2gMod->filler($this->modx->e2gMod->getTpl('file_thumb_file_tpl'), $filePhRow);
            $rowNum++;
        }

        return $output;
    }

}

return 'ModuleTagThumbProcessor';
