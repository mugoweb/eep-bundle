# eep - Ease eZ Platform/Ibexa DXP
A Symfony command bundle to support developers using eZ Platform/Ibexa DXP

## Work in progress
Currently developed against eZ Platform 2.5 LTS, Ibexa DXP 3.3 LTS & 4.6 LTS
```
master : targets Ibexa DXP 4.6 LTS
2.5-lts: targets eZPlatform 2.5 LTS
3.3-lts: targets Ibexa DXP 3.3 LTS
4.6-lts: targets Ibexa DXP 4.6 LTS

(archived)   
4.2    : targets Ibexa DXP 4.2
```
Bug reports and feature suggestions welcome.

## Installation
`composer require mugoweb/eep-bundle:dev-master`   
_Please check which version of the CMS master is targeting, or use version specific `dev-2.5-lts`, `dev-3.3-lts` or `dev-4.6-lts`_

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
eep:content:draftcreate      [eep:co:draftcreate] Create draft from content
eep:content:draftdelete      [eep:co:draftdelete] Deletes content draft version
eep:content:draftlist        [eep:co:draftlist] Returns content draft list
eep:content:draftpublish     [eep:co:draftpublish] Publish content draft
eep:content:info             [eep:co:info] Returns content information
eep:content:listfields       [eep:co:listfields] Returns content field list
eep:content:listversions     [eep:co:listversions] Returns content version list
eep:content:location         [eep:co:lo|eep:co:location] Returns main location id by content id
eep:content:related          [eep:co:related] Returns related content information
eep:content:republish        [eep:co:republish] Re-publishes content by id
eep:content:reverserelated   [eep:co:reverserelated] Returns reverse related content information
eep:content:update           [eep:co:update] Update content
eep:content:updatemeta       [eep:co:updatemeta] Update content meta data
eep:content:versiondelete    [eep:co:versiondelete] Deletes content version
eep:content:versioninfo      [eep:co:versioninfo] Returns content version information

eep:contentfield:fromstring  [eep:cf:fromstring] Set content field value from JSON string
eep:contentfield:info        [eep:cf:info] Returns content field information
eep:contentfield:tostring    [eep:cf:tostring] Returns content field value as JSON string

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
eep:location:swap            [eep:lo:swap] Swap source- and target locations

eep:search:search            [eep:sr:search] Returns search result information

eep:section:assigncontent    [eep:se:assigncontent] Assign content to section
eep:section:list             [eep:se:list] Returns section list
eep:section:listcontent      [eep:se:listcontent] Returns content list by section identifier

eep:user:info                [eep:us:info] Returns user information
eep:user:list                [eep:us:list] Returns user list
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
```
$ php bin/console eep:contenttype:listcontent folder --limit=6

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
_Note: Commands supporting the ```--hide-columns``` option allow for the results table display to be modified by hiding one or more of its columns._

### eep & friends: awk, xargs, grep ...
eep shines when it is used in combination with other command line utilities like awk, xargs, grep and many others.   

Due to the way the data tables are formatted, header and data columns use different column separators, they can be easily parsed and processed further.   
e.g.   
Only return content ids of folder objects.

```
$ php bin/console eep:contenttype:listcontent folder --limit=6 | awk '$1=="|" {print $2}'

1
41
45
49
50
51
```

Return location ids for those content ids.   

```
$ php bin/console eep:contenttype:listcontent folder --limit=6 | awk '$1=="|" {print $2}' > my_content_ids.txt

$ cat my_content_ids.txt | xargs -ICONTENTID php bin/console eep:content:location CONTENTID

2
43
48
51
52
53
```
_(Could also be combined into a single pipeline by omitting the file output/input step)_

## Example use cases

### Content reports
A site is preparing for content migration and requires a report of file type content within the content structure.   
e.g.

```
site/
├── article1
│   ├── file
│   ├── file
│   ├── file
│   ├── image
│   ├── image
│   └── video
└── article2
    ├── file
    ├── video
    ├── video
    ├── video
    └── video
```
Using the eep:location:subtree command and filtering via awk can generate a quick CSV report.

```
# get list of all location 2 subtree content
# from the tables data columns ($1=="|") select file rows (col $8=="file")
# from those rows, print CSV of: locationId, contentId, contentTypeIdentifier, pathString, urlAlias, and name
$ php bin/console eep:location:subtree 2 | awk '$1=="|" && $8=="file" {print $2","$4","$8","$10","$12","$24}'

# returns something like
60,59,file,/1/2/59/60/,/article1/file,File
61,60,file,/1/2/59/61/,/article1/file2,File
62,61,file,/1/2/59/62/,/article1/file3,File
66,65,file,/1/2/65/66/,/article2/file,File
67,66,file,/1/2/65/67/,/article2/file2,File
68,67,file,/1/2/65/68/,/article2/file3,File
```

### Content migration
A site has a collection of articles that each have children representing assets of various types.   
e.g.

```
site/
├── article1
│   ├── file
│   ├── file
│   ├── file
│   ├── image
│   ├── image
│   └── video
└── article2
    ├── file
    ├── video
    ├── video
    ├── video
    └── video
```

To allow easier re-use, all assets should be moved to a central location separated by type.   
e.g.

```
media/
└── article_assets
    ├── file
    ├── image
    └── video
```

Combining eep's commands and core command line tools (awk, xargs) migration pipelines can be created easily.

```
# get list of all article content
# get location ids (col $4) from the article list data columns ($1=="|")
# save the location ids to file
$ php bin/console eep:contenttype:listcontent article | awk '$1=="|" {print $4}' > article_location_ids.txt

# iterate over saved article location ids 
# from the article subtree data columns get the file location id (col $2) for file content type (col $8) items
# save the location ids to file
$ cat article_location_ids.txt | xargs -ILOCATION_ID php bin/console eep:location:subtree LOCATION_ID | awk '$1=="|" && $8=="file" {print $2}' > file_location_ids.txt

# move all file content to the central assets location for file content (location id: 100) without do prompting for user confirmation
$ cat file_location_ids.txt | xargs -ILOCATION_ID php bin/console eep:location:move --no-interaction LOCATION_ID 100

# repeat for image and video content
```

