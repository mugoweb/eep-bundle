# eep - Ease eZ Platform/Ibexa DXP
A Symfony command bundle to support developers using eZ Platform/Ibexa DXP

## Installation
`composer require mugoweb/eep-bundle:dev-master`   
_Please check which version of the CMS master is targeting, or use version specific `dev-2.5-lts` or `dev-3.3-lts`_

Afterward, you need to enable the bundle.   
### eZ Platform
Update `app/AppKernel.php`   
To enable the bundle in all environments, add `new MugoWeb\Eep\Bundle\MugoWebEepBundle(),` to the `$bundles` array.

### Ibexa DXP
Update `config/bundles.php`   
To enable the bundle in all environments, add `MugoWeb\Eep\Bundle\MugoWebEepBundle::class => ['all' => true],` to the array.

## Features
```
eep                          Ease eZ Platform/Ibexa DXP development (placeholder)

eep:cache:purge              [eep:ca:purge] Purge cache by tag(s)

eep:content:create           [eep:co:create] Create content at location
eep:content:delete           [eep:co:delete] Delete content
eep:content:info             [eep:co:info] Returns content information
eep:content:listfields       [eep:co:listfields] Returns content field list
eep:content:location         [eep:co:lo|eep:co:location] Returns main location id by content id
eep:content:related          [eep:co:related] Returns related content information
eep:content:republish        [eep:co:republish] Re-publishes content by id
eep:content:reverserelated   [eep:co:reverserelated] Returns reverse related content information
eep:content:update           [eep:co:update] Update content
eep:content:updatemeta       [eep:co:updatemeta] Update content meta data

eep:contentfield:info        [eep:cf:info] Returns content field information
eep:contentfield:tostring    [eep:cf:tostring] (experimental!) Returns string representation of content field information

eep:contenttype:info         [eep:ct:info] Returns content type information
eep:contenttype:list         [eep:ct:list] Returns content type list
eep:contenttype:listcontent  [eep:ct:listcontent] Returns content information by content type identifier
eep:contenttype:listfields   [eep:ct:listfields] Returns content type field list
eep:contenttypefield:info    [eep:ctf:info] Returns content type field information

eep:location:content         [eep:lo:co|eep:lo:content] Returns content id by location id
eep:location:copy            [eep:lo:copy] Copy source location to be child of target location
eep:location:delete          [eep:lo:delete] Delete location subtree
eep:location:hide            [eep:lo:hide] Hide location subtree
eep:location:info            [eep:lo:info] Returns location information
eep:location:move            [eep:lo:move] Move source location to be child of target location
eep:location:reveal          [eep:lo:reveal] Reveal location subtree
eep:location:subtree         [eep:lo:subtree] Returns subtree information

eep:section:assigncontent    [eep:se:assigncontent] Assign content to section
eep:section:list             [eep:se:list] Returns section list
eep:section:listcontent      [eep:se:listcontent] Returns content list by section identifier
```
Symfony's console help `-h` providers further information about command arguments and input/output formats.   
e.g.   
```
$ php bin/console eep:contenttype:listcontent -h

Usage:
  eep:contenttype:listcontent [options] [--] <content-type-identifier>
  eep:ct:listcontent

Arguments:
  content-type-identifier        Content type identifier

Options:
  -u, --user-id[=USER-ID]        User id for content operations [default: 14]
      --offset[=OFFSET]          Offset
      --limit[=LIMIT]            Limit
  -h, --help                     Display this help message
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
  -e, --env=ENV                  The Environment name. [default: "dev"]
      --no-debug                 Switches off debug mode.
      --siteaccess[=SITEACCESS]  SiteAccess to use for operations. If not provided, default siteaccess will be used
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Example output
`php bin/console eep:contenttype:listcontent folder --limit=6`
```
+-----------+----------------+-----------+------------------+----------------------------------+---------------------+
I eep:contenttype:listcontent [folder]                                                         I Results: 1 - 6 / 12 I
+-----------+----------------+-----------+------------------+----------------------------------+---------------------+
I contentId I mainLocationId I sectionId I currentVersionNo I remoteId                         I name                I
+-----------+----------------+-----------+------------------+----------------------------------+---------------------+
| 1         | 2              | 1         | 9                | 9459d3c29e15006e45197295722c7ade | eZ Platform         |
| 41        | 43             | 3         | 1                | a6e35cbcb7cd6ae4b691f3eee30cd262 | Media               |
| 45        | 48             | 4         | 1                | 241d538ce310074e602f29f49e44e938 | Setup               |
| 49        | 51             | 3         | 1                | e7ff633c6b8e0fd3531e74c6e712bead | Images              |
| 50        | 52             | 3         | 1                | 732a5acd01b51a6fe6eab448ad4138a9 | Files               |
| 51        | 53             | 3         | 1                | 09082deb98662a104f325aaa8c4933d3 | Multimedia          |
+-----------+----------------+-----------+------------------+----------------------------------+---------------------+
```

### eep & friends: awk, xargs, grep ...
eep shines when it is used in combination with other command line utilities like awk, xargs, grep and many others.   

Due to the way the data tables are formatted, header and data columns use different column separators, they can be easily parsed and processed further.   
e.g.   
Only return content ids of folder objects.
```
php bin/console eep:contenttype:listcontent folder --limit=6 | awk '$1=="|" {print $2}'

1
41
45
49
50
51
```

Return location ids for those content ids.   
```
php bin/console eep:contenttype:listcontent folder --limit=6 | awk '$1=="|" {print $2}' > my_content_ids.txt

cat my_content_ids.txt | xargs -ICONTENTID php bin/console eep:content:location CONTENTID

2
43
48
51
52
53
```
_(Could also be combined into a single pipeline by omitting the file output/input step)_



## Work in progress
Currently developed against eZ Platform 2.5 LTS, Ibexa DXP 3.3 LTS
```
master : targets eZPlatform 2.5 LTS   
2.5-lts: targets eZPlatform 2.5 LTS   
3.3-lts: targets Ibexa DXP 3.3 LTS
```
Bug reports and feature suggestions welcome.


