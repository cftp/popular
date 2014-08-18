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

- Popular uses OAuth, not Google "Service Accounts".
- In your Google Analytics property settings, don't include trailing slash in default URL, i.e. http://example.com NOT http://example.com/
