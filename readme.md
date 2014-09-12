### Using Google Analytics with Popular

- Use Composer to install the dependencies, including the Google PHP API client library (do you have a /vendor directory?)
- Login to [Google Developers Console](https://developers.google.com/console)
- Create a new project
- Go to APIs and auth > Credentials > Create new client ID. You should get a popup window.
- Choose Web application for Application Type
- Paste the "Google Analytics Client Redirect URL" from WordPress (Settings > Popular) into Authorised Redirect URL
- Click 'Create Client ID' button
- This will give you a Client ID, email address and client secret, paste these into WordPress settings, click Save Changes.
- Click Activate Google Analytics.  (Google may ask you to choose between accounts if you have more than one).
- Google will give you a page saying the app wants to access your data.

Note:

- By default, we only show page views for the last 30 days. You can adjust this in the plugin settings.
- Popular uses OAuth, not Google "Service Accounts".
- In your Google Analytics property settings, don't include trailing slash in default URL, i.e. http://example.com NOT http://example.com/

Dev Issues / ideas / beginnings of roadmap? (William Turrell, 2014-09-12):

- Views/age & Shares/age two separate columns as I could not think of a single algorithm to fairly combine the two.
- We probably need to be fetching stats more regularly than 24 hours (people will be interested in how things are doing straight after they go live), but also need some way of scaling for massive sites given all posts need to be updated (new posts every hour or two, older posts daily? Not foolproof though as sites often get surges in old content. Configurable?) Also, meta value of last_updated keys currently YYYY-MM-DD format and will require back-compatibility if time added.
- Similarly, the less often Views/age and Shares/age updated, the less reliable they will be.
- FB Like and Share sources make duplicate calls to API. If Picshare plugin installed, cftp_facebook_likes_source.php now gathers all the fields (like counts, comment counts etc.) at same time and for multiple URLs (each image in post). Slightly complicated because cftp_facebook_shares_source.php still needs to exist if Picshare *isn't* installed (and for any existing clients) and also to display the data for it's column in any case.
- I'd be inclined to drop facebook_shares_source and have a single Facebook source and single column for Facebook (or any other source) with combined/likes shares (but a link to get at the breakdown.)  My feeling is sorting by total interactions per 3rd-party site ought to be enough.
- Also, you can retrieve multiple URLs in one FB REST API call (as happens for Picshare) - so this could be done for ordinary posts too (though length of HTTP GET URL might become an issue, the same method appears to work fine with POST).
- There's a type in meta key (cfto_*) which I've persisted with to avoid breaking existing installations. Could write something to patch the database.
- Combine/serialise some of the options?
