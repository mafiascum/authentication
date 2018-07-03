function showHide(elem) {
    if (elem.selectedIndex !== 0) {
	//unhide the selected div
	document.getElementById('alt_dd').style.display = 'block';
	document.getElementById('alt_dt').style.display = 'block';
	document.getElementById('alt_password_dd').style.display = 'block';
	document.getElementById('alt_password_dt').style.display = 'block';
    } else {
	document.getElementById('alt_dd').style.display = 'none';
	document.getElementById('alt_dt').style.display = 'none';
	document.getElementById('alt_password_dd').style.display = 'none';
	document.getElementById('alt_password_dt').style.display = 'none';
    }
}

function verify_username(rootPath, inputElemId, outputElemId, formElemName) {
	
    const inputElem = $(`#${inputElemId}`);
    const outputElem = $(`#${outputElemId}`);

    fetch(`${rootPath}app.php/verify_username?q=${inputElem.val()}`)
        .then(response => response.json())
        .then(data => {
            if (data.length) {
                const user = data[0];
                const userAlreadyExists = outputElem.find(`input[value='${user.user_id}']`);
                if (!userAlreadyExists.length) {
                    outputElem.append([
                        `<li>`,
                        `<input type="button" class="button2" value="x" onclick="splice_user('${outputElemId}', '${user.user_id}')">`,
                        `<input type="hidden" name="${formElemName}[]" value="${user.user_id}">`,
                        `<a href="${user.profile}">${user.username}</a>`,
                        `</li>`
                    ].join(''));
                }
            }
            inputElem.val('');
        });
}

function splice_user(outputElemId, userId) {
    const outputElem = $(`#${outputElemId}`);
    const userToRemove = outputElem.find(`input[value='${userId}']`);
    if (userToRemove) {
        userToRemove.parent().remove();
    }
}