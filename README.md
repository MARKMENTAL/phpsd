# Legacy Stable Diffusion WebUI

A minimalistic, JavaScript-free web interface, front-end and processing server for Stable Diffusion, designed specifically for being accessed by legacy systems and browsers as clients. This interface relies on the API from AUTOMATIC1111's Stable Diffusion WebUI.

## Features

Two separate interfaces are provided:
- `index.php`: HTML 4.01+ compliant interface
- `legacy.php`: HTML 3.2 compliant interface for extremely old systems

Common features across both versions:
- No JavaScript dependencies
- Lightweight and fast loading
- Support for LORA models using square bracket syntax
- Image optimization with automatic PNG to JPG conversion
- Prompt saving and loading functionality
- Full configurability of key generation parameters:
  - Image dimensions
  - Sampling steps
  - CFG Scale
  - Choice of sampling methods

## Browser Compatibility

- `index.php`: Tested working on IE4+ and comparable browsers
- `legacy.php`: Tested working on IE3 and comparable browsers
- Both versions work on modern browsers

## Requirements

- Linux server with a suitable GPU; running a model in AUTOMATIC1111's Stable Diffusion WebUI https://github.com/AUTOMATIC1111/stable-diffusion-webui with API access (image generation back-end)
- Externally accessible Linux server running PHP 8+ to run the PHP front-end (Tested on frankenphp running on Debian 12, should also run with traditional PHP setups with apache/nginx, can run on the same server as the back-end)
- Web server (Apache, nginx, etc.)
- ImageMagick for image optimization
- Curl
- Client machine to access the front-end via web browser


## Installation

1. Ensure you have AUTOMATIC1111's Stable Diffusion WebUI running with the `--api` flag
2. Install PHP and ImageMagick on your system
3. Copy both `index.php` and `legacy.php` to your web server directory
4. Ensure the directory is writable by the web server for saved prompts and generated images
5. Access through your web browser:
   - Use `index.php` for HTML 4.01+ compatible browsers
   - Use `legacy.php` for HTML 3.2 compatible browsers

## Usage

1. Enter your prompt in the text area
2. Configure generation parameters as needed
3. Click "Generate Image" to create your image
4. Save frequently used prompts server-side using the "Save Prompt" feature
5. Load saved prompts from the dropdown menu

## LORA Usage

To use LORA models, include them in your prompt using square brackets:
```[lora-name:weight]```

Example:
```a beautiful landscape by [my-artist-lora:0.8]```

## Notes

- Images are automatically optimized and converted from PNG to JPG for faster loading
- The interface works without any client-side scripting
- All processing is done server-side
- Compatible with browsers from the late 1990s to now
- The HTML 3.2 version (`legacy.php`) provides basic functionality for extremely old mid 1990s browsers

## Why Two Versions?

- `index.php` provides a more refined interface with better styling and layout options, suitable for browsers supporting HTML 4.01+
- `legacy.php` strips down the interface to basic HTML 3.2 for maximum compatibility with vintage systems and browsers
- Both achieve the same core functionality with different levels of visual polish

## Screenshots

### Running on Windows NT 4.0 With IE 4
![Screenshot 2024-12-14 142743](https://github.com/user-attachments/assets/838095cb-0af7-4c6e-a140-78e8f7c65691)



### Running on Firefox ESR 115
![Screenshot 2024-12-14 143036](https://github.com/user-attachments/assets/9745d00f-57cc-4788-a17d-43782d7e6fa3)



## Contributing

Contributions are welcome! Please feel free to submit a Pull Request or fork.

## License

This project is MIT licensed.

## Credits

Built to work with AUTOMATIC1111's Stable Diffusion WebUI API:
https://github.com/AUTOMATIC1111/stable-diffusion-webui
