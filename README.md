### Simple Blood Bowl League Manager

This is a very basic, just-for-small-private-leagues manager for Blood Bowl. It features:

  - Seasons
  - Match programming and result recording
  - Team and player tracking
  - Coach tracking
  - Localization (English and Spanish are included, others are easily added)

It does not try to replace OBBLM, NAFLM, FEBB, nor any of these big suites with many stats, different leagues, etc.

### Requirements

SBBLM only needs a web server with PHP (>= 5.4) & SQLite support (it should be difficult to find such a server these days).
Please note I have not tested it extensively with every PHP version out there, just PHP7.1 on Lighttpd and PHP5.5 on Apache,
but there is no reason why it wouldn't work on any other platform. If it doesn't, please file a bug.

### Install

Just upload the files to your server, point a browser towards *<address>/install* and let SBBLM take care of the rest (this is
almost done, but still missing a couple of things).

### Notes

SBBLM comes with the LRB6 skills in Spanish. It is not very complicated to add new rulesets, but there might be legal 
complications for their distribution. However, since the changes are small, you can have a look at *rules/<files>.csv*
and change them wherever you want **prior to install**. I might include an English version soon, depending on requests.

If you wish to translate the app, you can have a look into the *dict/* folder. It is quite self-explanatory.

Re-install is as easy as deleting the database *db/db.sql* and going to the *install url* again.

This is a work in progress. I wrote it to manage my first Blood Bowl league, and answer to my requirements. I might add 
new features as I see them useful or necessary. I also accept requests, although their inclusion might depend on factors.