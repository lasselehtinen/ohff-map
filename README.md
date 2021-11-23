![Sample screenshot from the map](https://user-images.githubusercontent.com/1290186/143145700-eb391ffb-bddf-4e4f-a3e3-ea55737401fa.png)

# OHFF map

OHFF-map or OHFF-kartta in finnish is a small Laravel based web application to provide a Google Map view for WWFF references in Finland. It scrapes reference information from the official WWFF site for references and activations. The main goal is to provide a tool for activators find the next reference to go to. You can find a live version of this repository in:

[https://kartta.ohff.fi](https://kartta.ohff.fi)

## Commands

### update:references

This command parses the CSV from the WWFF directory and creates/updates the references with basic information.

### update:activations

This command parses scrapes each individual reference page for the latest activation.

### parse:protected_planet

Command parses the ShapeFiles provided by Protected Planet and stored in the repository. These files provide polygonal points for the references so that 
we can show the actual area in the map instead of just a single coordinate point.

## Contributing

Please consider contributing to the application if you can. Pull requests are more than welcome. For getting started, the repository contains a [Laravel Sail](https://laravel.com/docs/8.x/sail) configuration, so you only need a Docker runtime to start up the development environment.

## Security Vulnerabilities

If you discover a security vulnerability within this application, please send an e-mail to Lasse Lehtinen via [lasse.lehtinen@iki.fi](mailto:lasse.lehtinen@iki.fi). All security vulnerabilities will be promptly addressed.

## License

This project is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
