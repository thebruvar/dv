Moderation State Machine is a module intended for developers.

Although it provides a block (Moderation state switcher) with links for state transitions, its biggest power comes when writing plugins.

Drupal's [Workflow and Content Moderation](https://www.drupal.org/docs/8/core/modules/content-moderation/overview) modules provide UI for configuring Moderation states, transitions and attaching configured workflows to entities.
What it does not provide is a developer friendly [state machine](https://www.drupal.org/project/state_machine) like interface

This is where this module comes in.

It allows developers to write plugins of type "**ModerationStatePlugin**".
Plugins can subscribe to state transition events and define state change validations and violation message.

You can try an example plugin if you attach **Editorial workflow** to a Page
