# Symfony5

Well hi there! This repository holds the base code and script
for the [Symfony5 Tutorials](https://symfonycasts.com/tracks/symfony) on SymfonyCasts.

I containerised the application with docker to simplify the installation and the reuse of the project. Also, I added some features like:
* REST-Full API with versioning and serialization using FosRESTBundle
* Realtime push notification with Mercure protocol
* Front-end route generation with FosJSROutingBundle

## Setup

If you've just downloaded the code, congratulations!!

To get it working, follow these steps:

**Prerequisites**

Make sure you have:
    
* docker
* docker-compose
* make command

**Download docker images and build the project**

Run:

```
make init
```

Now check out the site at `https://localhost`

Have fun!

**Optional: Webpack Encore Assets**

This app uses Webpack Encore for the CSS, JS and image files. But
to keep life simple, the final, built assets are already inside the
project. So... you don't need to do anything to get thing set up!

If you *do* want to build the Webpack Encore assets manually, you
totally can! Make sure you have [yarn](https://yarnpkg.com/lang/en/)
installed and then run:

```
yarn install
yarn encore dev --watch
```

## Have Ideas, Feedback or an Issue?

If you have suggestions or questions, please feel free to
open an issue on this repository 🙂
