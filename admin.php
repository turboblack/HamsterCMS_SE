<?php
session_start();
error_reporting(0);

$config = include 'config.php';
$valid_username_hash = $config['username_hash'];
$valid_password_hash = $config['password_hash'];

$debuginfo = '';

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if (!empty($_POST['username']) && !empty($_POST['password'])) {
    if (
        password_verify($_POST['username'], $valid_username_hash) &&
        password_verify($_POST['password'], $valid_password_hash)
    ) {
        $_SESSION['loggedin'] = true;
        $_SESSION['_username'] = $_POST['username'];
    } else {
        $debuginfo = '<b style="color:#cc0000">Wrong login/password!</b>';
    }
}

$loggedin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

$filename = $_POST['filename'] ?? '';
$template = $_POST['template'] ?? '';
$directory = $_POST['directory'] ?? './files/';
$action_delete = isset($_POST['action_delete']);
$action_save = isset($_POST['action_save']);
$action_edit = isset($_POST['action_edit']);
$action_changedir = isset($_POST['action_changedir']);
$filelist = '';

if ($loggedin) {
    if ($action_changedir) {
        $filename = '';
        $template = '';
    }

    if ($action_save) {
        $newfilename = $_POST['newfilename'] ? trim(trim($_POST['newfilename']), '/') : '';
        $filecontent = $_POST['editbox'] ?? '';
        $filenameWithPath = $directory . $newfilename . '.txt';

        if (empty($newfilename)) {
            $debuginfo .= "<b style='color:#cc0000'>Error: filename is empty</b> ";
        } elseif (!is_dir($directory)) {
            $debuginfo .= "<b style='color:#cc0000'>Directory does not exist: $directory</b> ";
        } elseif (!is_writable($directory)) {
            $debuginfo .= "<b style='color:#cc0000'>Directory is not writable: $directory</b> ";
        } else {
            $h = fopen($filenameWithPath, 'w');
            if (!$h) {
                $debuginfo .= "<b style='color:#cc0000'>Cannot open file for writing: $filenameWithPath</b> ";
            } elseif (fwrite($h, $filecontent)) {
                $debuginfo .= 'File saved successfully. ';
                if ('./files/' === $directory || './includes/' === $directory) {
                    $savedLink = "<a href='$newfilename' target='_blank' style='color: black;'>Open saved file</a>";
                } elseif ('./blog/' === $directory) {
                    $savedLink = "<a href='$directory$newfilename' target='_blank' style='color: black;'>Open saved file</a>";
                } else {
                    $savedLink = "<a href='$filenameWithPath' target='_blank' style='color: black;'>Open saved file</a>";
                }
                $debuginfo .= $savedLink;
            } else {
                $debuginfo .= "<b style='color:#cc0000'>An error occurred while writing content data</b> ";
            }
            if ($h) {
                fclose($h);
            }

            // Template save
            $templateFile = $directory . $newfilename . '.txt_';
            if (!empty($template)) {
                $h = fopen($templateFile, 'w');
                if ($h && fwrite($h, $template)) {
                    $debuginfo .= ' Template saved.';
                } else {
                    $debuginfo .= "<b style='color:#cc0000'>Error saving template</b>";
                }
                if ($h) {
                    fclose($h);
                }
            } elseif (file_exists($templateFile)) {
                if (unlink($templateFile)) {
                    $debuginfo .= ' Template removed.';
                } else {
                    $debuginfo .= "<b style='color:#cc0000'>Error removing template</b>";
                }
            }
        }
    }

    if ($action_delete) {
        if (!file_exists($filename . '.txt')) {
            $debuginfo .= '<b style=color:#cc0000>Error: File does not exist</b>';
        } else {
            if (unlink($filename . '.txt') && (!file_exists($filename . '.txt_') || unlink($filename . '.txt_'))) {
                $debuginfo .= "<b style=color:#ffff>File \"$filename\" deleted</b>";
                $filename = '';
                $template = '';
            } else {
                $debuginfo .= '<b style=color:#cc0000>Error deleting file</b>';
            }
        }
    }

    if ($dh = opendir($directory)) {
        while (false !== ($file = readdir($dh))) {
            if (str_ends_with($file, '.txt')) {
                $current_file = "{$directory}{$file}";
                if (is_file($current_file)) {
                    $file = preg_replace('/(.*)\.txt$/i', '$1', $file);
                    $filelist .= '<option' . ($directory . $file == $filename ? ' selected' : '') . " value=\"$directory$file\">$file</option>";
                }
            }
        }
        closedir($dh);
    }

    function get_templates_sorted($selected)
    {
        $templates = [];
        if ($dh = opendir('./templates')) {
            while (false !== ($dir = readdir($dh))) {
                $subdir = './templates/' . $dir;
                if ('.' != $dir && '..' != $dir && is_dir($subdir) && file_exists("$subdir/index.html")) {
                    $templates[] = $dir;
                }
            }
            closedir($dh);
        }

        sort($templates);
        $return = '';
        foreach ($templates as $template) {
            $return .= '<option' . ($template == $selected ? ' selected' : '') . ">$template</option>";
        }

        return $return;
    }
}

function list_subdirectories($path, $current)
{
    if (is_dir($path)) {
        $dh = opendir($path);
        while (false !== ($dir = readdir($dh))) {
            if (is_dir($path . $dir) && '.' !== $dir && '..' !== $dir && './templates' !== ($path . $dir)) {
                $subdir = $path . $dir . '/';
                echo '<option' . ($subdir == $current ? ' selected' : '') . ">$subdir</option>";
                list_subdirectories($subdir, $current);
            }
        }
        closedir($dh);
    }
}
?>

<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
    <meta http-equiv='Content-Language' content='EN'>
    <style type="text/css">
        .form-style-2 { font: 13px Arial, Helvetica, sans-serif; }
        .form-style-2-heading { font-weight: bold; padding-bottom: 3px; }
        .form-style-2 label>span { margin-left: 20px; padding-right: 5px; }
        .form-style-2 input[type=submit], .form-style-2 input[type=button] {
            border: none; padding: 1px 15px; margin: 1px 5px;
            background: #08f; color: #fff; box-shadow: 1px 1px 4px #aaa;
            border-radius: 3px; min-width: 75px;
        }
        .form-style-2 input[type=submit]:hover, .form-style-2 input[type=button]:hover {
            background: #048; color: #fff;
        }
        .form-style-2 input[type='text'], .form-style-2 select, .form-style-2 input[type='password'] {
            width: 150px;
        }
        .form-style-2 input[type=submit].red {
            background: #d00;
        }
        .form-style-2 input[type=submit].red:hover {
            background: #a00;
        }
        #loginform {
            margin: 0px 0px 10px 0px; border: 1px solid #008; padding: 20px; background: #fff;
            border-radius: 10px;
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            text-align: center;
        }
        #loginform p { text-align: right; }
        #debuginfo { color: #339900; }
        #debuginfo:hover { color: #888; }
    </style>
</head>
<body>
<div class="form-style-2">
    <center>
        <div class="form-style-2-heading">HamsterCMS</div>
        <form action="" method="post">
            <div id="debuginfo"><?= $debuginfo; ?>&nbsp;</div>
            <?php if (!$loggedin) { ?>
                <div id="loginform">
                    <p>
                        <label for="username"><span>Login:</span></label>
                        <input id="username" name="username" type="text" placeholder="Enter login">
                    </p>
                    <p>
                        <label for="password"><span>Password:</span></label>
                        <input id="password" name="password" type="password" placeholder="Enter password">
                    </p>
                    <input id="login" type="submit" value="Login">
                </div>
            <?php } else { ?>
                <?php if (!empty($filelist)) { ?>
                    <label for="filename"><span>Page:</span>
                        <select id="filename" name="filename" onchange="return document.getElementById('action_edit').click();">
                            <option value=""></option>
                            <?= $filelist; ?>
                        </select>
                    </label>
                    <input type="submit" id="action_edit" name="action_edit" value="Edit"/>
                <?php } ?>
                <label for="directory"><span>Directory:</span>
                    <select id="directory" name="directory" onchange="return document.getElementById('action_changedir').click();">
                        <option value=""></option>
                        <option>./</option><?php list_subdirectories('./', $directory); ?>
                    </select>
                </label>
                <input type="submit" id="action_changedir" name="action_changedir" value="Change"/>
                <?php
                if (!empty($filename) && file_exists("$filename.txt")) {
                  $filecontent = file_get_contents("$filename.txt");
                  $template = (file_exists("$filename.txt_") ? file_get_contents("$filename.txt_") : ''); ?>
<br>
<style>
.format-buttons input[type="button"] {
    padding: 4px 4px;
    margin: 1px 1px;
    font-size: 16px;
    min-width: auto;
    width: auto;
	background: #663300;
	color: #ffff00;
}
</style>
<div class="format-buttons">
    <input type="button" value="br" title="Line break" onclick="insert('<br>')">
    <input type="button" value="hr" title="Horizontal line" onclick="insert('<hr>')">&nbsp;&nbsp;&nbsp;
    <input type="button" value="B" title="Bold" onclick="wrap('<b>', '</b>')">
    <input type="button" value="I" title="Italic" onclick="wrap('<i>', '</i>')">
    <input type="button" value="U" title="Underline" onclick="wrap('<u>', '</u>')">
    <input type="button" value="S" title="Strikethrough" onclick="wrap('<s>', '</s>')">&nbsp;&nbsp;&nbsp;

    <input type="button" value="H1" title="Heading H1" onclick="wrap('<h1>', '</h1>')">
    <input type="button" value="H2" title="Heading H2" onclick="wrap('<h2>', '</h2>')">
    <input type="button" value="TT" title="Monospaced (teletype)" onclick="wrap('<tt>', '</tt>')">&nbsp;&nbsp;&nbsp;

    <input type="button" value="p" title="Paragraph" onclick="wrap('<p>', '</p>')">

    <input type="button" value="sub" title="Subscript" onclick="wrap('<sub>', '</sub>')">
    <input type="button" value="sup" title="Superscript" onclick="wrap('<sup>', '</sup>')">&nbsp;&nbsp;&nbsp;

    <input type="button" value="left" title="Align left" onclick="wrap('<div align=\'left\'>', '</div>')">
    <input type="button" value="center" title="Center" onclick="wrap('<center>', '</center>')">
    <input type="button" value="right" title="Align right" onclick="wrap('<div align=\'right\'>', '</div>')">

    <input type="button" value="ul" title="Unordered list" onclick="wrap('<ul>\n<li>', '</li>\n</ul>')">
    <input type="button" value="ol" title="Ordered list" onclick="wrap('<ol>\n<li>', '</li>\n</ol>')">
    <input type="button" value="li" title="List item" onclick="wrap('<li>', '</li>')">
	<input type="button" value="Marquee+" title="Advanced marquee" onclick="insertMarquee()"><br>

    <label for="fontFaceSelect">Font:</label>
    <select id="fontFaceSelect" onchange="insertFontFace(this.value)">
      <option value="">--Select font--</option>
      <option value="Arial">Arial</option>
      <option value="Courier New">Courier New</option>
      <option value="Times New Roman">Times New Roman</option>
      <option value="Verdana">Verdana</option>
      <option value="Tahoma">Tahoma</option>
      <option value="Georgia">Georgia</option>
      <option value="Impact">Impact</option>
    </select>

    <label for="fontSizeSelect">Font size:</label>
    <select id="fontSizeSelect" onchange="insertFontSize(this.value)">
      <option value="">--Select size--</option>
      <option value="1">1 (small)</option>
      <option value="2">2</option>
      <option value="3">3 (default)</option>
      <option value="4">4</option>
      <option value="5">5</option>
      <option value="6">6</option>
      <option value="7">7 (large)</option>
    </select>

    <label for="textColorSelect">Text color:</label>
    <select id="textColorSelect" onchange="insertTextColor(this.value)">
      <option value="">--Select color--</option>
      <option value="#000000">Black</option>
      <option value="#800000">Dark red</option>
      <option value="#008000">Dark green</option>
      <option value="#808000">Olive</option>
      <option value="#000080">Navy</option>
      <option value="#800080">Purple</option>
      <option value="#008080">Teal</option>
      <option value="#c0c0c0">Silver</option>
      <option value="#808080">Gray</option>
      <option value="#ff0000">Red</option>
      <option value="#00ff00">Green</option>
      <option value="#ffff00">Yellow</option>
      <option value="#0000ff">Blue</option>
      <option value="#ff00ff">Magenta</option>
      <option value="#00ffff">Cyan</option>
      <option value="#ffffff">White</option>
    </select>
<br>
    <label for="bgColorSelect">Text background:</label>
    <select id="bgColorSelect" onchange="insertBgColor(this.value)">
      <option value="">--Select background--</option>
      <option value="#ffff00">Yellow</option>
      <option value="#ffcc99">Peach</option>
      <option value="#cccccc">Gray</option>
      <option value="#99ff99">Light green</option>
      <option value="#99ccff">Light blue</option>
      <option value="#ff9999">Pink</option>
      <option value="#ffffff">White</option>
      <option value="#000000">Black</option>
    </select>&nbsp;&nbsp;&nbsp;

    <input type="button" value="code" title="Monospaced code" onclick="wrap('<code>', '</code>')">
    <input type="button" value="pre" title="Preformatted text" onclick="wrap('<pre>', '</pre>')">
    <input type="button" value="quote" title="Quote" onclick="wrap('<blockquote>', '</blockquote>')">
	<input type="button" value="Anchor" title="Anchor name" onclick="insertAnchor()">
	<input type="button" value="#Link" title="Link to anchor" onclick="insertAnchorLink()">&nbsp;&nbsp;&nbsp;

    <input type="button" value="Link" title="Hyperlink (target=_blank)" onclick="insertLink()">
    <input type="button" value="@" title="Email link (mailto:)" onclick="insertMail()">
    <input type="button" value="Img" title="Insert image" onclick="insertImg()">

</div>

<textarea id="editbox" name="editbox" cols="90" rows="25"><?= htmlspecialchars($filecontent); ?></textarea>
<p>
    <label for="newfilename"><span>Save as:</span></label>
    <input type="text" id="newfilename" name="newfilename" value="<?= basename(htmlspecialchars($filename)); ?>">
    <label for="template"><span>Template:</span></label>
    <select id="template" name="template">
        <option value=""></option>
        <?= get_templates_sorted($template); ?>
    </select>
    <input type="submit" name="action_save" value="Save" />
    <input class="red" type="submit" name="action_delete" value="Delete" onClick="return confirm('Do you really want to delete this page?');" />

<script type="text/javascript">
function wrap(tagStart, tagEnd) {
  var t = document.getElementById('editbox');
  if (document.selection) {
    t.focus();
    var sel = document.selection.createRange();
    sel.text = tagStart + sel.text + tagEnd;
  } else if (typeof t.selectionStart != "undefined") {
    var start = t.selectionStart;
    var end = t.selectionEnd;
    var txt = t.value;
    var selected = txt.substring(start, end);
    var replaced = tagStart + selected + tagEnd;
    t.value = txt.substring(0, start) + replaced + txt.substring(end);
  } else {
    t.value += tagStart + tagEnd;
  }
  t.focus();
}

function insert(tag) {
  var t = document.getElementById('editbox');
  t.value += tag;
  t.focus();
}

function insertFontFace(face) {
  if (!face) return;
  wrap('<font face="' + face + '">', '</font>');
  document.getElementById('fontFaceSelect').value = '';
}

function insertFontSize(size) {
  if (!size) return;
  wrap('<font size="' + size + '">', '</font>');
  document.getElementById('fontSizeSelect').value = '';
}

function insertTextColor(color) {
  if (!color) return;
  wrap('<font color="' + color + '">', '</font>');
  document.getElementById('textColorSelect').value = '';
}

function insertBgColor(color) {
  if (!color) return;
  wrap('<span style="background-color:' + color + '">', '</span>');
  document.getElementById('bgColorSelect').value = '';
}

function insertLink() {
  var url = prompt("Enter URL:", "http://");
  if (!url) return;
  var txt = prompt("Link text:", "site");
  if (!txt) txt = url;
  insert('<a href="' + url + '" target="_blank">' + txt + '</a>');
}

function insertMail() {
  var mail = prompt("Enter e-mail:", "example@site.com");
  if (!mail) return;
  var txt = prompt("Link text:", mail);
  insert('<a href="mailto:' + mail + '">' + txt + '</a>');
}

function insertImg() {
  var src = prompt("Enter image URL:", "image.jpg");
  if (!src) return;
  insert('<img src="' + src + '">');
}

function insertAnchor() {
  var name = prompt("Enter anchor name:", "section1");
  if (!name) return;
  insert('<a name="' + name + '"></a>');
}

function insertAnchorLink() {
  var name = prompt("Enter anchor name to link to:", "section1");
  if (!name) return;
  var txt = prompt("Link text:", name);
  insert('<a href="#' + name + '">' + txt + '</a>');
}

function insertMarquee() {
  var text = prompt("Enter the scrolling text:", "Scrolling text here...");
  if (!text) return;

  var direction = prompt("Direction? (left, right, up, down)", "left");
  if (!direction) direction = "left";

  var behavior = prompt("Behavior? (scroll, slide, alternate)", "scroll");
  if (!behavior) behavior = "scroll";

  var speed = prompt("Speed? (scrollamount - 1 to 100)", "6");
  if (!speed) speed = "6";

  var result = '<marquee direction="' + direction + '" behavior="' + behavior + '" scrollamount="' + speed + '">' + text + '</marquee>';
  
  insert(result);
}
</script>
<?php
                } ?>
<input type="submit" name="logout" value="Logout"></p>
<?php } ?>
</form>
</center>
</div>
</body>
</html>