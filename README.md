# wc-pwinty
WooCommerce Pwinty API Integration Plug In Proof of Concept
****
THIS PLUGIN IS A LONG WAY OFF PRODUCTION READY. THIS  DRAFT VERSION IS ONLY FOR VERY EARLY BETA TESTING IN DEVELOPMENT ENVIRONMENTS. 
IF YOU INSTALL THIS ON YOUR LIVE SITE AND THE SKY FALLS DOWN DON'T BLAME ME!

**Also the code isn't exactly tidy or well commented just yet...*

## Why? ##
After working on a number of Photography sites and trying [Sell Media](https://graphpaperpress.com/plugins/sell-media/) and [BPTPI](https://wordpress.org/plugins/bulk-photo-to-product-importer-extension-for-woocommerce/) etc, and then seeing WooThemes put out a fairly weak (IMO) [Photography Extension](http://www.woothemes.com/products/woocommerce-photography/) I remembered beginning to investigate a plugin that hooked up WooComm with the [Pwinty API](http://pwinty.com). This plugin is those first ideas drafted together.

## What? ##

Essentially the plugin creates a custom post type (Pwinty Albums) and a custom taxonomy (Print Variations) which it uses to generate and manage WooCommerce Variable Products. It then interacts with the Woo order process to create and send orders automatically to be printed and dispatched via the Pwinty API.

## Usage ##

###Limitations###

The order actions buttons in the order admin list view (`...` `Submit For Printing`) will only work with a code update coming in WooComm 2.3.
![](src="https://github.com/SteveHoneyNZ/wc-pwinty/blob/master/Capture.JPG")

I've got plenty of improvements which will be added as Issues eventually, lots of scope for more complex functionality. This beta is kept as simple as possible intentionally.

The only major limitation currently with Pwinty is they don't yet support International delivery, so you'd be limited to in-country sales for now. I'll add more detail on this to an Issue.

###Set Up###

First stop is WooCommerce -> Settings -> Integration.

If you're not interested in the Pwinty side of things, and just want to see the product creation process in action, then all you need to fill in here is an upload directory to use (field isn't sanitized currently so lowercase and hyphens, no spaces for now).

The other settings are self explanatory, you will need to sign up for a sandbox account at [pwinty.com](http://pwinty.com) to obtain an API Key and Merchant ID (email address is all that's needed). This version of the plugin is edited to only use the sandbox regardless of the setting.

The only setting required in the Pwinty account settings is to save your callback URL which is http://yourdevsite.com/wc-api/pwintyhandler/  
Remember you must [enable PrettyPermalinks](http://codex.wordpress.org/Using_Permalinks#Choosing_your_permalink_structure) in order for the API handler to function.

###Prepare Images###

Image compression and file preparation is well beyond the scope here. I'd imagine any end user being competent enough in LightRoom/Photoshop to prepare images for printing. File size should simply be the smallest you can manage while maintaining print quality, generally anything over 5mb a file is going to be pretty damn slow to upload 100's of images even on a solid bandwidth.

The key here is pixel dimensions and aspect ratio.
First of there's clearly little point offering the same image in `4x6` and `5x7` print sizes - if the aspect ratio is different then the image won't correctly fill the available space in one version. 

Taking the obvious example of 3:2 ratio images, then the pixel dimensions simply need to match the recommended dimensions of the largest print size you want to offer.
 
So say you want to sell `P24x36` , `8x12` and `4x6` prints then you'd upload your images at 3600px x 5400px. 
You can send an oversize file no problem as long as the aspect ratio matches.

###Create Products###

Print variations must first be set up (left menu Pwinty Albums -> Print Variations) as per the available print types according to your country and quality settings. Refer [to the list here](http://www.pwinty.com/PhotoTypes) for available print types. Each print variation you add simply needs to have a slug which matches the pwinty value for *"Type" in system* (`4x6`, `8x12` etc.). 

You can price and name Print Variations as you like (remember the 'Name' appears in a dropdown on the frontend so not too long).

The price set is persistent throughout WooCommerce, change the price here and every matching product variation will have the price adjusted accordingly.

Products are created from the 'Pwinty Album' post type editor. Any of the media insertion methods work but the Gallery interface is most logical.

When each photo is uploaded in the gallery editor a full res duplicate is copied to a directory specified by the user in the integration settings. This is the version Pwinty will fetch.

This plugin was developed with the excellent and highly recommended [Image Watermark plugin](https://wordpress.org/plugins/image-watermark/) installed, and they play together perfectly well as far as I could find. 

All other gallery settings are irrelevant, except the 'Number of Columns' setting which will alter the thumbnail grid visible in the post editor.

You also need to select the Print Variations you want to create variable products for, and the WooCommerce Categories and Tags you'd like applied to the created products.

Finally any text you add to the post editor will be added as the general description for all the products you create, and the excerpt will become the short description (not required and still editable as normal product by product).

Then simply Publish or Update the post and all being well your products are live!

###Orders###

Numerous interactions so I'll just list them (all order notes are admin only not visible/sent to customer):

Order processed through checkout  -->  Pwinty order is created with customer details, no images added yet. `pwinty_order_id ` is visible as a custom field on the order edit screen, a pwinty callbck adds an order note "Pwinty Order Created"

Order marked processing (by payment gateway or user dependent on payment method) --> Images added to pwinty order, each image adds order note with success message or displaying the error from pwinty.

Order marked submitted (custom order status) --> final user approval of order, submission status is checked with pwinty, order submitted to pwinty if valid, pwinty callback adds "Pwinty Order SUccessfully submitted." order note, if not valid an email is sent to the admin address containing the submission report (general errors plus image by image).

Order completed and dispatched by pwinty --> callback marks order complete in WooComm, order tracking link is added as custom field and displayed in WooComm order complete email as link if available.

....LOTS MORE TO DO HERE PLEASE BE PATIENT!....

