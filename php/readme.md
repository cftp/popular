# cftp_analytics

This is a primary object that contains and handles the objects below. It requires a `cftp_analytics_factory`

# cftp_analytics_factory

This object constructs other object. It may be useful in the future to turn this into an interface so that a test object factory can be implemented.

# cftp_analytics_cron_task

This class runs the necessary data retrieval and setting of meta values and other data at set intervals for an individual analytics service.

# cftp_analytics_settings_page

This class implements the settings page used for configuring cron timings, authentication and account selection

# cftp_analytics_source

A basic interface to abstract the details of each analytics service

# cftp_null_analytics_source

A null implementation of `cftp_analytics_source`, intended for testing or use of the null pattern.

# cftp_google_analytics_source

An implementation of `cftp_analytics_source` that provides Google Analytics support.

