## Selligent client functional library in PHP

A simple library to fetch and sync data from and to Selligent. 

#### Setup for running the example
 
- download and install composer in the root directory of this project: https://getcomposer.org/download/
- run `php composer.phar install` in the root directory
- copy the `.env.example` file and rename it to `.env`, then fill in the properties.
- run `php examples/authenticate-and-fetch-profiles.php`. This will show you a JSON response of
 profiles when everything is working and an error when there's still something wrong. See the example for 
 an implementation of this library.

Notes: 
- the dotenv library is only needed for the example, how and where you store your credentials is all up to you
