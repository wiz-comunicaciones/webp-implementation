# OctoberCMS WebP Plugin

Implements a simple twig filter that will change file extension to `.webp`. The plugin will only do this on files existing within OctoberCMS. If no `.webp`file is found, fallbacks to original file extension.

Consider using some strategy on how to generate `.webp` image versions of your files. You can find a good tutorial on how to create and serve WebP images [here](https://www.digitalocean.com/community/tutorials/how-to-create-and-serve-webp-images-to-speed-up-your-website).

Since we are only changing the file extension, this integrates well with image processing plugins like [Matthew Pawley's](https://github.com/toughdeveloper) [`ToughDeveloper.ImageResizer`](https://github.com/toughdeveloper/oc-imageresizer-plugin) and such, and chains well with filters like `|media` and `|app`. Just add the `|webp` filter at the end.

Usage
--
In any CMS layout, page or partial do:

```twig
<img src="{{ 'path-to-file.jpg'|media|webp }}>
```

Setting up automatic WebP image generation on ubuntu
--

The next steps will setup a monitoring service to your app's `storage` folder. This is based on a post by [Abdullatif Eymash](https://www.digitalocean.com/community/users/abdullatif).

We will need `cwebp` for converting images and `inotifywait` to monitor our app directory:
```bash
sudo apt-get install webp inotify-tools
```

### Create an image convertion script

Create the `webp-convert.sh` script in your user's home directory. This script will convert all unconverted files into WebP keeping the originals:
```bash
vi ~/webp-convert.sh
```

Add the following:
```bash
#!/bin/bash

# converting JPEG images
find $1 -type f -and \( -iname "*.jpg" -o -iname "*.jpeg" \) \
-exec bash -c '
webp_path=$(sed 's/\.[^.]*$/.webp/' <<< "$0");
if [ ! -f "$webp_path" ]; then 
  cwebp -quiet -q 90 "$0" -o "$webp_path";
fi;' {} \;

# converting PNG images
find $1 -type f -and -iname "*.png" \
-exec bash -c '
webp_path=$(sed 's/\.[^.]*$/.webp/' <<< "$0");
if [ ! -f "$webp_path" ]; then 
  cwebp -quiet -lossless "$0" -o "$webp_path";
fi;' {} \;
```

Remember to make the script executable:
```bash
chmod a+x ~/webp-convert.sh
```

Run the script wherever you want to recursively generate WebP images. I'll be working on OctoberCMS' `storage` dir.
```bash
./webp-convert.sh /path/to/octobermcs/storage
```

This will create a WebP version of every jpeg, jpg and png file found.

### Create a directory watcher script
```bash
vi ~/webp-watchers.sh
```

Add the following:
```bash
#!/bin/bash
echo "Setting up watches.";

# watch for any created, moved, or deleted image files
inotifywait -q -m -r --format '%e %w%f' -e close_write -e moved_from -e moved_to -e delete $1 \
| grep -i -E '\.(jpe?g|png)$' --line-buffered \
| while read operation path; do
  webp_path="$(sed 's/\.[^.]*$/.webp/' <<< "$path")";
  if [ $operation = "MOVED_FROM" ] || [ $operation = "DELETE" ]; then # if the file is moved or deleted
    if [ -f "$webp_path" ]; then
      $(rm -f "$webp_path");
    fi;
  elif [ $operation = "CLOSE_WRITE,CLOSE" ] || [ $operation = "MOVED_TO" ]; then  # if new file is created
     if [ $(grep -i '\.png$' <<< "$path") ]; then
       $(cwebp -quiet -lossless "$path" -o "$webp_path");
     else
       $(cwebp -quiet -q 90 "$path" -o "$webp_path");
     fi;
  fi;
done;
```

Remember to make the script executable:
```bash
chmod a+x ~/webp-watchers.sh
```

Run the script on the desired dir (I usually handle theme pics myself, so in this example I will be setting it up on the OctoberCMS' `storage` dir only):
```bash
./webp-watchers.sh /path/to/octobercms/storage > /path/to/logs/webp-watchers.log 2>&1 &
```

Todo
--
1. Manage and customize desired WebP image version names: initially, the plugin expects both original and WebP versions to have the same file name with different extensions.
2. Add webp image generation capabilities so that the plugin doesn't rely on external server config.
