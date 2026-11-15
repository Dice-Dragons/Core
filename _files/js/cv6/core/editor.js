(() =>
{
	'use strict'

	const registerCv6EditorIcons = () =>
	{
		if (typeof FroalaEditor === 'undefined')
		{
			return
		}

		const iconsEl = document.querySelector('.js-cv6EditorIcons')
		if (!iconsEl)
		{
			return
		}

		let icons = {}
		try
		{
			icons = JSON.parse(iconsEl.textContent || '{}')
		}
		catch (e)
		{
			console.error('Error parsing cv6 editor icons', e)
			return
		}

		if (Object.keys(icons).length === 0)
		{
			return
		}

		FroalaEditor.DefineIconTemplate('cv6_html', '[HTML]')

		for (const [cmd, html] of Object.entries(icons))
		{
			FroalaEditor.DefineIcon(cmd, {
				template: 'cv6_html',
				HTML: html,
			})
		}
	}

	XF.on(document, 'editor:first-start', registerCv6EditorIcons)
	XF.on(document, 'xf:page-load', registerCv6EditorIcons)

	if (document.readyState === 'complete' || document.readyState === 'interactive')
	{
		registerCv6EditorIcons()
	}
	else
	{
		document.addEventListener('DOMContentLoaded', registerCv6EditorIcons)
	}
})()
