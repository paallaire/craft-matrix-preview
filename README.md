# Matrix Preview

Adds a "Show image preview" button next to "New entry" on your native Craft
CMS 5 Matrix fields. Clicking it opens a modal listing the available entry
types (each with a preview image) so you can add a block in one click.

## Installation

Add to composer.json

```bash
{
   "type": "vcs",
   "url": "https://github.com/paallaire/craft-matrix-preview"
}
```
Example

```bash
  "repositories": [
    {
      "type": "composer",
      "url": "https://composer.craftcms.com",
      "canonical": false
    },
    {
      "type": "vcs",
      "url": "https://github.com/paallaire/craft-matrix-preview"
    }
  ]
```

```bash
composer require paallaire/matrix-preview
```

Then go to **Settings → Plugins** in the CP and install "Matrix Preview".

## Usage

1. Go to **Settings → Matrix Preview** (link in the CP's left-hand menu).
2. Toggle on each Matrix field that should display the button.
3. Place an image for each entry type at the path indicated in the "Image location" column of the table, e.g. `web/matrix-preview/{{ myField_handle }}/{{ myEntryType_handle }}.jpg`.

## License

MIT
