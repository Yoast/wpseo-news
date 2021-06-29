/* eslint-disable no-useless-concat */
/* eslint-disable no-useless-escape */
/* eslint-disable max-len */
// Custom task
// Rem defaultChangelogEntrys: "Other:\n* Includes every change in Yoast SEO core " + "VERSIONNUMBER" + ". See the [core changelog](https://wordpress.org/plugins/wordpress-seo/#developers).\n",

module.exports = {
	"wpseo-news": {
		options: {
			readmeFile: "./README.md",
			releaseInChangelog: /[#] \d+\.\d+(\.\d+)?\: /g,
			matchChangelogHeader: /Changelog\n=========\n/ig,
			newHeadertemplate: "Changelog\n=========\n\n" + "### " + "VERSIONNUMBER" + ": " + "DATESTRING"  + "\n",
			matchCorrectLines: "### " + "VERSIONNUMBER" + "(.|\\n)*?(?=(### \\d+[\.\\d]+\: |### Earlier versions|$))",
			matchCorrectHeader: "### " + "VERSIONNUMBER" + "(.|\\n)*?\\n(?=(\\w\+?:\\n|### \\d+[\.\\d]+\: |### Earlier versions|$))",
			matchCleanedChangelog: "### " + "VERSIONNUMBER" + "(.|\\n)*### Earlier versions",
			replaceCleanedChangelog: "### Earlier versions",
			defaultChangelogEntries: "",
			useANewLineAfterHeader: false,
			useEditDistanceCompare: true,
			commitChangelog: true,
		},
	},
};
