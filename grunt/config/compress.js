module.exports = {
	artifactFiles: {
		options: {
			archive: "artifact.zip",
			level: 9,
		},
		files: [
			{
				expand: true,
				cwd: "artifact/",
				src: ["**"],
				dest: "./",
			},
		],
	},
}
