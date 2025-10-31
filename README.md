# 🏡 Real Estate
оооло
Real Estate is a comprehensive starter kit specially designed to jump-start your real estate website on Statamic. Featuring **30+ pre-designed sets**, this versatile kit empowers you to create unique property listings, agent profiles, testimonials, and more with ease. Whether you're building a website for a real estate agency, showcasing property portfolios, or setting up a rental platform, Real Estate equips you with the resources to do it all elegantly and efficiently.

## Why is Real Estate the perfect starting point?
Our meticulous attention to detail makes Real Estate an **intuitive and dynamic** Starter Kit, so you can avoid coding altogether and focus on what matters most – your content. It comes with an easy-to-use **control panel** that lets you manage property listings, agent contacts, and customer testimonials without breaking a sweat. What's more, each component is rigorously **tested** for flawless responsiveness across **all devices**, allowing you to pick your favorite layout elements and personalize your site by embedding your brand identity and content.

## Features of Real Estate
* 30+ real estate-focused component designs
* Customizable with Tailwind CSS for brand consistency
* Component-driven design with Antlers components.
* Intuitive property list builder from the Control Panel
* Dedicated Property Detail Page with gallery, features, and contact form
* SEO-optimized for property listings and local searches
* Configurable sitemap and SEO settings tailored for real estate business
* Dynamic navigation for Featured Properties, Categories, and Agent Listings
* Advanced search and filter capabilities for property listings
* Dynamic maps using Mapbox auto-update with real-time property coordinates from the Single Properties 
* Integrated Contact Form for property form with styled fields
* [Iconsax](https://iconsax.io/) for high-quality real estate icons
* Support for multiple agents or a single agent profile
* Breakpoint plugin for meticulous responsive design customization
* Enhanced user interaction powered by Alpine.js


## Installation
Before running the frontend tooling, make sure Node.js and npm are available on your machine. The starter kit targets the active LTS release of Node.js (or newer). You can download an installer from [nodejs.org](https://nodejs.org/) or install via a version manager such as [nvm](https://github.com/nvm-sh/nvm) or [asdf](https://asdf-vm.com/).

Verify the tooling is ready by running:

```bash
node -v
npm -v
```

If the commands above report "command not found", install Node.js/npm before attempting `npm run dev` or `npm run build`.

To set up Real Estate, refer to the [Starter Kit installation instructions](https://statamic.dev/starter-kits/installing-a-starter-kit). Ensure you're running **Statamic 4.x** for compatibility.

### Installing into an existing site

```bash
php please starter-kit:install lucky-media/real-estate
```

### Installing via the Statamic CLI Tool
Use the Statamic CLI for quick setup:

```bash
statamic new my-agency-site lucky-media/real-estate
```

## Configuring
Real Estate is designed to be ready out of the box. After installation, simply use the control panel to fine-tune your site. The kit is future-proofed to receive smooth updates with the latest versions of Statamic.

### Content
On installation, Real Estate comes with pre-configured sample listings, agent bios, and posts to give your site a head start. These can be personalized or removed entirely. Collections include:

* Property Listings
* Agent Profiles
* Testimonials
* Blog Posts
* City Listings

Furthermore, the Starter Kit includes tailored real estate components:

* Hero Section (3 sets)
* Agents Section (3 sets)
* Dynamic Headers for Listing Pages (2 sets)
* Featured Categories (1 set)
* Featured Listings (1 set)
* Cities Section (3 sets)
* Related Listings (1 set)
* Multiple Features (4 sets)
* Search Properties (1 set)
* Card Columns Section (1 set)
* Testimonial (3 sets)
* Section with Image (2 sets)
* Contact Us (1 set)
* Map Section (1 set)
* CTA Section (1 set)
* Property Detail Layout
* Agent Detail Layout
* About Page Layout
* Dynamic Headers for Listing Pages (3 variants)
* Customizable 404 Page
* Footer


### Agents
We have added an Agents collection for real estate agents, such as their Title, Biography, and links to Social Media profiles. When assigning an agent to a property listing, their detailed information will be beautifully displayed, offering potential clients a personal connection and building trust.

### Property Search
We've improved the search bar to quickly find properties based on various criteria, which are fully customizable:

* For Sale or Rent: Toggle between properties available for sale or rent.
* Min Price/Max Price: Set your budget range for precise results.
* Location: Specify your preferred location for properties.
* Bedrooms: Choose the number of bedrooms in the property.
* Bathrooms: Select the number of bathrooms you desire.
* Floor Area: Set the minimum and maximum square footage.
* Based on the category: Filter properties by type - Apartment, Office, Studio, etc.

### Single Property
* Property Gallery: View a gallery of high-quality images showcasing the property.
* Address: Provide the complete address, including city and postal code.
* Bedrooms/Bathrooms: Indicate the number of bedrooms and bathrooms.
* Style: Describe the architectural style of the property (e.g., Modern Loft).
* Property Size: Highlight the total square footage of the property.
* Description: Present a detailed description of the property, including its unique features, history, and potential uses.
* Multimedia
  * View Video Tour: If available, provide a link to a video tour, allowing users to explore the property virtually.
* Property Details Section
  * Property Size: Reiterate the size of the property for emphasis.
  * Land Area: Specify the total land area of the property.
  * Year Built: Indicate the year the property was constructed.
  * Listing by Agent: Display the name and role of the listing agent.
* Forms
  * Schedule a Tour
  * Request a Home Tour: Allow users to express interest in scheduling a tour.
  * Contact Us: Provide multiple contact options, including phone number and email address.

### Icons and Images
For visually engaging features sections, SVG icons are leveraged to create a sophisticated look. You'll find a selection of these icons in the `resources/svg` directory. To customize or extend the icon set, explore [Iconsax](https://iconsax.io/), where you can simply copy the desired SVG code for use on your site. 

For demonstration purposes, the starter kit includes sample images from [unDraw](https://undraw.co/). 
If you choose to use these resources in a production environment, remember to provide appropriate attribution, which you can conveniently place in the footer.


### MAPBOX INTEGRATION
 
#### Obtain a Mapbox access token:

* Go to the Mapbox website (https://www.mapbox.com/) and create an account if you don't have one.
* Generate an access token from your Mapbox account dashboard. This token will be used to authenticate your requests to Mapbox services.

#### Add the Access Token to the .env file

* Open the `.env` file.
* Add this line `VITE_MAPBOX_ACCESS_TOKEN=`.
* After the = sign, paste your Mapbox access token obtained in step 1.
* Save the .env file.

#### Usage

To use Mapbox with the Real Estate starter kit, follow these steps:

* Access the Statamic CP on your website.
* Depending on your desired usage:
  * To use Mapbox inside the `Properties Section`, navigate Globals -> Theme. Go to the Settings tab and enable the toggle for `Has Map`.
  * To use Mapbox inside the `Map Section`, navigate to the set and fill in the `Latitude` and `Longitude` inputs.

### Globals
Globals are efficiently organized into different themes for easy customization:

* General
  * Sitename – Update to your agency's name.
  * Logo – Replace the default Cloud logo with your agency's logo (SVG recommended for best resolution and scalability).
* Favicon
  * We've generated all necessary favicon files for you but using our placeholder logo. Update these with your branding for consistency across web browsers. Real Favicon Generator (https://realfavicongenerator.net/) was used to create these and note that a `site.webmanifest` file is dynamically generated.
* Footer
  * Logo Display – Choose whether to display your logo in the footer.
  * Copyright – Update with your agency’s legal information.
  * Description – Add a brief description or tagline for your agency.
* Sitemap
  * Collections – Select collections to be included in the sitemap (Pages and Posts are set by default).
  * Change Frequency – Advise search engines how often the sitemap should be re-crawled.
  * Priority – Indicate to search engines the relative importance of pages on your site.

### SEO
SEO tools are embedded in the page editing interface within the control panel. A dedicated SEO section for each listing ensures that your content is optimized for search engines. The provided Seotamic add-on makes it easy to adjust meta tags, including dynamically generated Open Graph images for improved social sharing:

* Meta
  * Title – Customize the title displayed in search engine results.
  * Title prepend/append – Add a prefix or suffix to the title, drawing from the general SEO settings.
  * Meta Description – Craft a compelling summary for search engine listings. If left blank, search engines will auto-generate a description.
  * Canonical URL – Automatically reflects the page’s URL, but can be customized if needed.
* Social
  * Open Graph Title/Description – Customize how your content appears when shared on social platforms such as Facebook.
  * Twitter Title/Description – Tailor how your content is presented on Twitter.
  * Image – If no specific image is set, a default image will be utilized for social sharing. 

For posts, our custom ViewModel `App\ViewModels\OgImage.php` dynamically generates an Open Graph image based on the Hero Image of a post, enhancing social media visibility. If a static Open Graph image is preferred for each post, this ViewModel can be removed from the `Posts` collection setup.

### Styling
TailwindCSS powers Real Estate's design system; you can alter the primary colors and fonts to curate distinctive styles.

## Fonts & Icons
Choose from a wide variety of integrated fonts and icons to further personalize and enhance the user interface.

## Compiling Assets
Make use of Laravel Vite for asset compilation:
* `npm install` - installs dependencies.
* `npm run dev` - development mode.
* `npm run build` - compiles for production.

## Commercial Use
Real Estate is a commercial starter kit - ensure you acquire a license via the [Statamic Marketplace](https://statamic.com/starter-kits/luckymedia/real-estate) before deploying it in your project.

## 🐞 Bugs and 💡 Feature Requests
Please refer to the issues tab to submit a Bug or a Feature Request.

## Credits
Real Estate was brought to you by the lovely team at [Lucky Media](https://www.luckymedia.dev/). We are a digital agency focused on professional, innovative, user-oriented web design & development. If you have any project in mind, feel free to contact us.

Last but not least thanks to the creators/contributors of the following packages which made Real Estate a reality:

* Statamic CMS
* Tailwind CSS
* Alpine.js
